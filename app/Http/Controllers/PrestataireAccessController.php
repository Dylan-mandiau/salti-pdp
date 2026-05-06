<?php

namespace App\Http\Controllers;

use App\Models\Pdp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Accès public du prestataire via le lien magique (/p/{token}).
 * Pas d'authentification — le token sert d'accès.
 */
class PrestataireAccessController extends Controller
{
    /**
     * Affiche le formulaire pour le prestataire (sa partie EE seulement).
     */
    public function show(string $token, Request $request): View|RedirectResponse
    {
        $pdp = $this->resolveToken($token);

        $this->logAudit($pdp, 'viewed', $request);

        return view('prestataire.show', [
            'pdp' => $pdp,
            'token' => $token,
        ]);
    }

    /**
     * Auto-save côté prestataire — uniquement les champs EE.
     */
    public function autoSave(string $token, Request $request)
    {
        $pdp = $this->resolveToken($token);

        // Le prestataire peut modifier tant qu'il n'a pas signé personnellement
        // (même après avoir 'soumis' à SALTI : permet de corriger des erreurs
        // humaines genre date d'habilitation mal saisie).
        if ($pdp->signed_by_prestataire_at || in_array($pdp->status, [Pdp::STATUS_SIGNED, Pdp::STATUS_ARCHIVED, Pdp::STATUS_CANCELLED])) {
            return response()->json(['error' => 'PDP verrouillé'], 423);
        }

        $payload = $request->input('data', []);

        // Validation SIRET : si saisi, doit faire exactement 14 chiffres
        if (isset($payload['ee']['siret'])) {
            $siret = preg_replace('/\D/', '', (string) $payload['ee']['siret']);
            if ($siret !== '' && strlen($siret) !== 14) {
                return response()->json([
                    'error' => 'SIRET invalide : 14 chiffres requis.',
                    'field' => 'ee.siret',
                ], 422);
            }
            $payload['ee']['siret'] = $siret; // normalise (que des chiffres)
        }

        // 1) Mise à jour des données JSON (clés autorisées côté prestataire)
        $allowed = ['ee', 'autres_risques', 'risques', 'documents_remis_salti', 'permis_feu'];
        $data = $pdp->data;
        foreach ($allowed as $key) {
            if (isset($payload[$key])) {
                if ($key === 'autres_risques') {
                    // Remplacement complet du tableau (pas de merge profond)
                    $data[$key] = $payload[$key];
                } else {
                    $data[$key] = array_replace_recursive($data[$key] ?? [], $payload[$key]);
                }
            }
        }
        $pdp->data = $data;
        $pdp->save();

        // 2) Mise à jour des intervenants (table dédiée)
        // Format attendu :
        //   intervenants: [
        //     { nom_prenom: 'Tony', habilitations: [{ code: 'R489-3', label: '...', validity: 'YYYY-MM-DD' }, ...] },
        //     ...
        //   ]
        // Rétrocompat : si 'habilitation' (string) est présent au lieu de 'habilitations',
        // on convertit en tableau à 1 élément.
        if (isset($payload['intervenants']) && is_array($payload['intervenants'])) {
            // Stratégie simple : on supprime tout et on recrée. Acceptable car table petite (3-4 lignes max)
            $pdp->intervenants()->delete();
            foreach ($payload['intervenants'] as $iv) {
                $habs = [];
                if (isset($iv['habilitations']) && is_array($iv['habilitations'])) {
                    foreach ($iv['habilitations'] as $h) {
                        $label = trim($h['label'] ?? '');
                        if ($label === '') continue;
                        $habs[] = [
                            'code' => $h['code'] ?? null,
                            'label' => $label,
                            'validity' => ! empty($h['validity']) ? $h['validity'] : null,
                        ];
                    }
                } elseif (! empty($iv['habilitation'])) {
                    // rétrocompat
                    $habs[] = [
                        'code' => null,
                        'label' => $iv['habilitation'],
                        'validity' => ! empty($iv['habilitation_validity']) ? $iv['habilitation_validity'] : null,
                    ];
                }

                if (empty($iv['nom_prenom']) && empty($habs)) {
                    continue;
                }

                // Pour la rétrocompat, on remplit aussi habilitation/habilitation_validity
                // avec la première habilitation de la liste.
                $primary = $habs[0] ?? null;
                $pdp->intervenants()->create([
                    'nom_prenom' => $iv['nom_prenom'] ?? '',
                    'habilitation' => $primary['label'] ?? null,
                    'habilitation_validity' => $primary['validity'] ?? null,
                    'habilitations' => $habs ?: null,
                    'is_representant' => ! empty($iv['is_representant']),
                ]);
            }
        }

        return response()->json(['saved_at' => now()->format('H:i:s'), 'status' => 'ok']);
    }

    /**
     * Upload d'un fichier par le prestataire (CACES, habilitations, photos, etc.)
     */
    public function uploadDocument(string $token, Request $request)
    {
        $pdp = $this->resolveToken($token);

        // Modifiable tant que le prestataire n'a pas signé
        if ($pdp->signed_by_prestataire_at || in_array($pdp->status, [Pdp::STATUS_SIGNED, Pdp::STATUS_ARCHIVED, Pdp::STATUS_CANCELLED])) {
            return response()->json(['error' => 'PDP verrouillé'], 423);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10 MB
            'type' => 'required|in:caces,autorisation_conduite,habilitation,permis_feu,fds,plan_acces,convention_pret,autre',
            'label' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = uniqid('doc_').'.'.$ext;
        $relativeDir = "pdp/{$pdp->uuid}/uploads";
        $absoluteDir = storage_path('app/'.$relativeDir);

        // Crée le dossier avec permissions correctes
        if (! is_dir($absoluteDir)) {
            if (! @mkdir($absoluteDir, 0775, true) && ! is_dir($absoluteDir)) {
                \Log::error('Impossible de créer le dossier uploads', ['dir' => $absoluteDir]);
                return response()->json(['error' => 'Erreur serveur : impossible de créer le dossier de stockage.'], 500);
            }
        }
        if (! is_writable($absoluteDir)) {
            \Log::error('Dossier uploads non writable', ['dir' => $absoluteDir]);
            return response()->json(['error' => 'Erreur serveur : dossier non accessible en écriture.'], 500);
        }

        $absolutePath = $absoluteDir.'/'.$filename;
        $relativePath = $relativeDir.'/'.$filename;

        // Capture le originalName + size AVANT le move() (l'objet UploadedFile devient
        // invalide après déplacement et getSize()/getClientOriginalName() peuvent renvoyer null/0)
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
        $size = $file->getSize();

        try {
            $file->move($absoluteDir, $filename);
        } catch (\Throwable $e) {
            \Log::error('Échec move() upload', ['err' => $e->getMessage(), 'dest' => $absolutePath]);
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du fichier : '.$e->getMessage()], 500);
        }

        // Vérification physique : le fichier doit exister à la destination
        if (! file_exists($absolutePath) || filesize($absolutePath) === 0) {
            \Log::error('Fichier introuvable après move', ['path' => $absolutePath]);
            return response()->json(['error' => 'Le fichier n\'a pas pu être enregistré sur le serveur.'], 500);
        }

        $doc = $pdp->documents()->create([
            'type' => $request->input('type', 'autre'),
            'label' => $request->input('label'),
            'path' => $relativePath,
            'original_filename' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_by' => 'prestataire',
        ]);

        $this->logAudit($pdp, 'uploaded_document', $request, [
            'filename' => $doc->original_filename,
            'type' => $doc->type,
        ]);

        return response()->json([
            'id' => $doc->id,
            'filename' => $doc->original_filename,
            'type' => $doc->type,
            'label' => $doc->label,
            'size' => $doc->size,
            'download_url' => route('prestataire.download-document', ['token' => $token, 'doc' => $doc->id]),
        ]);
    }

    public function deleteDocument(string $token, int $docId, Request $request)
    {
        $pdp = $this->resolveToken($token);
        $doc = $pdp->documents()->where('id', $docId)->where('uploaded_by', 'prestataire')->first();
        if (! $doc) abort(404);

        if (file_exists(storage_path('app/'.$doc->path))) {
            @unlink(storage_path('app/'.$doc->path));
        }
        $doc->delete();

        $this->logAudit($pdp, 'deleted_document', $request);

        return response()->json(['ok' => true]);
    }

    public function downloadDocument(string $token, int $docId, Request $request)
    {
        $pdp = $this->resolveToken($token);
        $doc = $pdp->documents()->where('id', $docId)->first();
        if (! $doc) abort(404, 'Document introuvable.');
        $absolutePath = storage_path('app/'.$doc->path);
        if (! file_exists($absolutePath)) {
            \Log::warning('Document DB existant mais fichier physique manquant', ['doc_id' => $docId, 'path' => $absolutePath]);
            abort(404, 'Le fichier joint a été perdu sur le serveur. Veuillez le ré-uploader.');
        }
        return $this->fileResponse($absolutePath, $doc->original_filename, $request);
    }

    /**
     * Télécharge / Affiche le Permis feu pré-rempli pour ce PDP.
     * Régénéré à la volée pour avoir toujours la dernière version.
     */
    public function downloadPermisFeu(string $token, Request $request, \App\Services\AnnexDocumentsGenerator $gen)
    {
        $pdp = $this->resolveToken($token);
        $relativePath = $gen->generatePermisFeu($pdp);
        return $this->fileResponse(storage_path('app/'.$relativePath), 'permis-feu-'.$pdp->uuid.'.pdf', $request);
    }

    /**
     * Télécharge / Affiche la Convention de prêt pré-remplie.
     */
    public function downloadConventionPret(string $token, Request $request, \App\Services\AnnexDocumentsGenerator $gen)
    {
        $pdp = $this->resolveToken($token);
        $relativePath = $gen->generateConventionPret($pdp);
        return $this->fileResponse(storage_path('app/'.$relativePath), 'convention-pret-'.$pdp->uuid.'.pdf', $request);
    }

    /**
     * Télécharge / Affiche le Plan d'accès de l'agence (s'il existe).
     */
    public function downloadPlanAcces(string $token, Request $request)
    {
        $pdp = $this->resolveToken($token);
        if (! $pdp->agency?->access_plan_path) abort(404);
        return $this->fileResponse(
            storage_path('app/'.$pdp->agency->access_plan_path),
            $pdp->agency->access_plan_filename ?? 'plan-acces.pdf',
            $request
        );
    }

    /**
     * Helper : retourne un fichier en mode preview (inline) si ?inline=1, sinon en download.
     */
    private function fileResponse(string $absolutePath, string $filename, Request $request)
    {
        if ($request->boolean('inline')) {
            return response()->file($absolutePath, [
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }
        return response()->download($absolutePath, $filename);
    }

    /**
     * Télécharge / Affiche le PDP principal (le PDF généré).
     *
     * Règle de sécurité : le téléchargement (attachment) du PDP n'est autorisé
     * qu'APRÈS la signature du prestataire — un document non signé n'a pas
     * vocation à être archivé tel quel. La consultation (?inline=1) reste
     * toujours possible pour respecter l'obligation de consultation préalable.
     */
    public function downloadMainPdp(string $token, Request $request, \App\Services\PdpHtmlPdfGenerator $gen)
    {
        $pdp = $this->resolveToken($token);
        $relativePath = $gen->generate($pdp);
        $absolutePath = storage_path('app/'.$relativePath);
        $filename = 'plan-prevention-'.$pdp->uuid.'.pdf';

        // Avant signature presta : forcer le mode preview, jamais de download
        if (! $pdp->signed_by_prestataire_at) {
            return response()->file($absolutePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="plan-prevention-preview.pdf"',
            ]);
        }

        return $this->fileResponse($absolutePath, $filename, $request);
    }

    /**
     * Un salarié intervenant signe l'attestation de prise de connaissance du PDP.
     * Stocke la signature + date sur la ligne pdp_intervenants correspondante.
     */
    public function signIntervenant(string $token, int $intervenantId, Request $request)
    {
        $pdp = $this->resolveToken($token);

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        $iv = $pdp->intervenants()->where('id', $intervenantId)->first();
        if (! $iv) abort(404);

        $sig = $request->input('signature_data');
        $iv->update([
            'signature_data' => $sig,
            'date_signature' => now()->toDateString(),
        ]);

        $this->logAudit($pdp, 'attestation_signed', $request, [
            'intervenant_id' => $intervenantId,
            'nom_prenom' => $iv->nom_prenom,
            'is_representant' => $iv->is_representant,
        ]);

        // Si ce salarié est aussi le représentant EE, sa signature sert aussi
        // comme signature_ee finale (évite la double signature).
        if ($iv->is_representant) {
            $data = $pdp->data;
            $data['signature_ee'] = $sig;
            $data['signature_ee_fonction'] = $data['signature_ee_fonction'] ?? 'Représentant EE';
            // Auto-fill du Permis feu si requis
            if (! empty($data['documents_remis_ee']['permis_feu'])) {
                $data['permis_feu']['signed_by_employer'] = $sig;
                if (empty($data['permis_feu']['date_delivrance'])) {
                    $data['permis_feu']['date_delivrance'] = now()->toDateString();
                }
            }
            $pdp->update([
                'data' => $data,
                'signed_by_prestataire_at' => now(),
            ]);
            $this->logAudit($pdp, 'signed_by_prestataire_via_representant', $request, [
                'intervenant_id' => $intervenantId,
            ]);
        }

        return response()->json([
            'ok' => true,
            'date_signature' => $iv->date_signature->format('d/m/Y'),
            'is_representant' => $iv->is_representant,
            'signed_by_prestataire' => $pdp->fresh()->signed_by_prestataire_at !== null,
        ]);
    }

    /**
     * Le prestataire soumet sa partie pour validation par SALTI.
     */
    public function submit(string $token, Request $request): RedirectResponse
    {
        $pdp = $this->resolveToken($token);

        $pdp->update([
            'status' => Pdp::STATUS_AWAITING_VALIDATION,
            'submitted_by_prestataire_at' => now(),
        ]);

        $this->logAudit($pdp, 'submitted_by_prestataire', $request);

        return redirect()->route('prestataire.show', $token)
            ->with('success', 'Votre partie a été soumise à SALTI pour validation.');
    }

    /**
     * Signature du prestataire.
     *
     * Si le Permis feu est requis sur ce PDP, la signature et la date de
     * délivrance sont automatiquement reportées sur le Permis feu — pas
     * besoin pour le presta de signer deux fois.
     *
     * Si SALTI a déjà signé, on finalise le PDP (statut → SIGNED, PDF final
     * généré, hash SHA-256 stocké) — sinon le statut restait bloqué à
     * "À signer" alors que les 2 parties avaient signé.
     */
    public function sign(string $token, Request $request, \App\Services\PdpHtmlPdfGenerator $generator): RedirectResponse
    {
        $pdp = $this->resolveToken($token);

        $request->validate([
            'signature_data' => 'required|string',
            'signature_fonction' => 'required|string|max:255',
        ]);

        $data = $pdp->data;
        $data['signature_ee'] = $request->input('signature_data');
        $data['signature_ee_fonction'] = $request->input('signature_fonction');

        // Auto-report sur le Permis feu (signature de l'employeur + date de délivrance)
        if (! empty($data['documents_remis_ee']['permis_feu'])) {
            $data['permis_feu']['signed_by_employer'] = $request->input('signature_data');
            // On respecte une date_delivrance déjà saisie manuellement par le presta
            if (empty($data['permis_feu']['date_delivrance'])) {
                $data['permis_feu']['date_delivrance'] = now()->toDateString();
            }
        }

        $pdp->update([
            'data' => $data,
            'signed_by_prestataire_at' => now(),
        ]);

        $this->logAudit($pdp, 'signed_by_prestataire', $request);

        // Si SALTI a déjà signé, finaliser le PDP (statut → SIGNED + PDF final)
        $pdp->finalizeIfBothSigned($generator);

        return redirect()->route('prestataire.show', $token)
            ->with('success', 'Signature enregistrée. Merci !');
    }

    /**
     * Résout le token et retourne le PDP, ou 404/410.
     */
    private function resolveToken(string $token): Pdp
    {
        $pdp = Pdp::where('magic_token', $token)->first();
        if (! $pdp) {
            abort(404, 'Lien introuvable ou révoqué.');
        }
        if ($pdp->magic_token_expires_at && $pdp->magic_token_expires_at->isPast()) {
            abort(410, 'Ce lien a expiré. Demandez à SALTI un nouveau lien.');
        }
        if ($pdp->status === Pdp::STATUS_CANCELLED) {
            abort(410, 'Ce PDP a été annulé.');
        }
        return $pdp;
    }

    private function logAudit(Pdp $pdp, string $action, Request $request, array $payload = []): void
    {
        $pdp->auditLogs()->create([
            'actor' => 'prestataire:'.substr($pdp->magic_token, 0, 8),
            'action' => $action,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
