<?php

namespace App\Http\Controllers;

use App\Models\Pdp;
use App\Models\Prestataire;
use App\Models\User;
use App\Services\PdpHtmlPdfGenerator;
use App\Services\PdpPdfGenerator;
use App\Services\PdpValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Contrôleur SALTI : création, édition, validation et signature des PDP.
 */
class PdpController extends Controller
{
    public function __construct(
        private PdpHtmlPdfGenerator $generator,
        private PdpPdfGenerator $legacyGenerator,
        private PdpValidator $validator,
    ) {}

    /**
     * Tableau de bord : liste des PDP de l'agence connectée.
     * Le compte QSE central voit tous les PDP de toutes les agences.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $query = Pdp::query()->with(['prestataire', 'agency'])->latest();

        if (! $user->isQseAdmin()) {
            $query->where('agency_id', $user->id);
        }

        $pdps = $query->get();

        return view('pdp.dashboard', [
            'pdps' => $pdps,
            'stats' => [
                'drafts' => $pdps->where('status', Pdp::STATUS_DRAFT)->count(),
                'awaiting_prestataire' => $pdps->where('status', Pdp::STATUS_AWAITING_PRESTATAIRE)->count(),
                'awaiting_validation' => $pdps->where('status', Pdp::STATUS_AWAITING_VALIDATION)->count(),
                'signed' => $pdps->where('status', Pdp::STATUS_SIGNED)->count(),
            ],
        ]);
    }

    /**
     * Étape 1 : choix du mode (Présentiel / À distance).
     */
    public function chooseMode(): View
    {
        // Si QSE central : passer la liste des agences pour le dropdown.
        $agencies = Auth::user()->isQseAdmin()
            ? User::where('role', User::ROLE_AGENCY)->orderBy('city')->orderBy('name')->get(['id', 'name', 'city'])
            : collect();

        return view('pdp.choose-mode', compact('agencies'));
    }

    /**
     * Création d'un nouveau PDP avec le mode choisi.
     * - Compte d'agence : le PDP est assigné à cette agence (sa propre id).
     * - Compte QSE : le QSE doit choisir à quelle agence assigner le PDP.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'mode' => 'required|in:presentiel,distance',
            'donneur_ordre_nom' => 'required|string|max:255',
        ];

        // Si QSE, l'agence cible est obligatoire
        if ($user->isQseAdmin()) {
            $rules['agency_id'] = 'required|integer|exists:users,id';
        }

        $validated = $request->validate($rules);

        // Détermine l'agence propriétaire du PDP
        if ($user->isQseAdmin()) {
            $agency = User::findOrFail($validated['agency_id']);
            // Garde-fou : on ne peut pas assigner à un autre admin QSE par erreur
            if ($agency->isQseAdmin()) {
                return back()->with('error', 'Le PDP doit être assigné à une agence, pas au compte QSE central.');
            }
            $agencyId = $agency->id;
            $defaultOtp = $agency->require_otp_by_default;
        } else {
            $agency = $user;
            $agencyId = $user->id;
            $defaultOtp = $user->require_otp_by_default;
        }

        // Pré-remplir les infos EU dans data avec les coordonnées de l'agence
        // (dispo dès l'étape 1 du wizard sans saisie manuelle)
        $initialData = Pdp::emptyData();
        $initialData['eu']['agence'] = $agency->city ?? $agency->name;
        $initialData['eu']['donneur_ordre'] = $validated['donneur_ordre_nom'];
        $initialData['eu']['address'] = $agency->address;
        $initialData['eu']['phone'] = $agency->phone;

        $pdp = Pdp::create([
            'agency_id' => $agencyId,
            'mode' => $validated['mode'],
            'status' => Pdp::STATUS_DRAFT,
            'donneur_ordre_nom' => $validated['donneur_ordre_nom'],
            'require_otp' => $defaultOtp,
            'data' => $initialData,
        ]);

        $this->logAudit($pdp, 'created', $request);

        return redirect()->route('pdp.edit', $pdp)->with('success', 'Plan de prévention créé.');
    }

    /**
     * Wizard de remplissage : page courante (1 à 6 pour SALTI).
     */
    public function edit(Pdp $pdp, Request $request): View
    {
        $this->authorizePdp($pdp);

        // Auto-correction d'un PDP coincé en "À signer" alors que les 2 parties
        // ont signé (cas qui pouvait arriver avant le fix de finalizeIfBothSigned).
        $pdp->finalizeIfBothSigned($this->generator);

        $step = (int) $request->query('step', 1);
        $step = max(1, min(6, $step));

        $prestataires = Auth::user()->isQseAdmin()
            ? Prestataire::orderBy('raison_sociale')->get()
            : Prestataire::where('agency_id', Auth::id())->orderBy('raison_sociale')->get();

        // Liste des agences :
        // - QSE central : toutes les agences (pour le dropdown)
        // - Compte d'agence : seulement la sienne (pré-rempli)
        $agencies = User::where('role', User::ROLE_AGENCY)
            ->orderBy('city')
            ->orderBy('name')
            ->get(['id', 'name', 'city']);

        // Validation de cohérence (alertes affichées à droite, blocage si erreurs)
        $validation = $this->validator->check($pdp);

        return view('pdp.edit', [
            'pdp' => $pdp,
            'step' => $step,
            'prestataires' => $prestataires,
            'agencies' => $agencies,
            'validation' => $validation,
        ]);
    }

    /**
     * Auto-save d'un champ (appelé en AJAX par le wizard).
     */
    public function autoSave(Pdp $pdp, Request $request)
    {
        $this->authorizePdp($pdp);

        if (in_array($pdp->status, [Pdp::STATUS_SIGNED, Pdp::STATUS_ARCHIVED])) {
            return response()->json(['error' => 'PDP verrouillé'], 423);
        }

        $data = $pdp->data;
        $payload = $request->input('data', []);
        $data = array_replace_recursive($data, $payload);
        $pdp->data = $data;

        // Quelques champs structurés à mettre à jour aussi
        if ($request->filled('prestataire_id')) {
            $pdp->prestataire_id = $request->input('prestataire_id');
        }
        if ($request->filled('donneur_ordre_nom')) {
            $pdp->donneur_ordre_nom = $request->input('donneur_ordre_nom');
        }

        $pdp->save();

        return response()->json([
            'saved_at' => now()->format('H:i:s'),
            'status' => 'ok',
        ]);
    }

    /**
     * Génère un lien magique d'accès pour le prestataire (mode "à distance").
     * Le SALTI copie ensuite le lien depuis le bandeau pour le transmettre
     * (email perso, SMS, messagerie…). Pas d'email envoyé par le système.
     */
    public function sendToPrestataire(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        $pdp->generateMagicToken(7);
        $pdp->update([
            'status' => Pdp::STATUS_AWAITING_PRESTATAIRE,
            'sent_to_prestataire_at' => now(),
        ]);

        $this->logAudit($pdp, 'sent_to_prestataire', $request);

        return redirect()->route('pdp.edit', $pdp)
            ->with('success', '✓ Lien magique généré (valable 7 jours). Copiez-le depuis le bandeau ci-dessus et transmettez-le au prestataire.');
    }

    /**
     * Régénère un nouveau lien magique pour ce PDP (en cas d'expiration ou de perte).
     */
    public function regenerateMagicLink(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        if ($pdp->mode !== Pdp::MODE_DISTANCE) {
            return back()->with('error', 'Cette action est réservée aux PDP en mode "À distance".');
        }

        $token = $pdp->generateMagicToken(7);
        $this->logAudit($pdp, 'magic_link_regenerated', $request);

        return back()->with('success', "✓ Nouveau lien magique généré (valable 7 jours) :\n".$pdp->magicLinkUrl());
    }

    /**
     * Aperçu PDF en streaming (génération à la volée pour preview).
     */
    public function preview(Pdp $pdp): BinaryFileResponse
    {
        $this->authorizePdp($pdp);

        $relativePath = $this->generator->generate($pdp);
        $absolutePath = storage_path('app/'.$relativePath);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="apercu-pdp.pdf"',
        ]);
    }

    /**
     * Téléchargement du PDF final (une fois signé).
     * Utilise response()->download() qui force le Content-Disposition: attachment
     * (response()->file() avec un header attachment est ignoré par certains navigateurs).
     */
    public function download(Pdp $pdp)
    {
        $this->authorizePdp($pdp);

        // Régénère systématiquement à la demande pour avoir la version la plus à jour
        $relativePath = $this->generator->generate($pdp);
        $pdp->update(['final_pdf_path' => $relativePath]);

        $absolutePath = storage_path('app/'.$relativePath);
        $filename = 'plan-prevention-'.$pdp->uuid.'.pdf';

        return response()->download($absolutePath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Helper : retourne un fichier en mode preview (inline) si ?inline=1, sinon en download.
     * Utilisé par les méthodes downloadXxx ci-dessous pour permettre le double bouton
     * Consulter / Télécharger côté SALTI.
     */
    private function fileOrDownload(string $absolutePath, string $filename, Request $request)
    {
        if ($request->boolean('inline')) {
            return response()->file($absolutePath, [
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }
        return response()->download($absolutePath, $filename);
    }

    /**
     * Télécharge / Affiche le Permis feu pré-rempli (côté SALTI).
     */
    public function downloadPermisFeu(Pdp $pdp, Request $request, \App\Services\AnnexDocumentsGenerator $gen)
    {
        $this->authorizePdp($pdp);
        $relativePath = $gen->generatePermisFeu($pdp);
        return $this->fileOrDownload(storage_path('app/'.$relativePath), 'permis-feu-'.$pdp->uuid.'.pdf', $request);
    }

    /**
     * Télécharge / Affiche la Convention de prêt pré-remplie (côté SALTI).
     */
    public function downloadConventionPret(Pdp $pdp, Request $request, \App\Services\AnnexDocumentsGenerator $gen)
    {
        $this->authorizePdp($pdp);
        $relativePath = $gen->generateConventionPret($pdp);
        return $this->fileOrDownload(storage_path('app/'.$relativePath), 'convention-pret-'.$pdp->uuid.'.pdf', $request);
    }

    /**
     * Télécharge / Affiche le Plan d'accès de l'agence rattachée à ce PDP.
     */
    public function downloadPlanAcces(Pdp $pdp, Request $request)
    {
        $this->authorizePdp($pdp);
        if (! $pdp->agency?->access_plan_path) abort(404);
        return $this->fileOrDownload(
            storage_path('app/'.$pdp->agency->access_plan_path),
            $pdp->agency->access_plan_filename ?? 'plan-acces.pdf',
            $request
        );
    }

    /**
     * Upload d'un document côté SALTI (présentiel ou correction distance).
     * Mêmes catégories que côté prestataire pour rester cohérent.
     */
    public function uploadDocument(Pdp $pdp, Request $request)
    {
        $this->authorizePdp($pdp);

        if (in_array($pdp->status, [Pdp::STATUS_SIGNED, Pdp::STATUS_ARCHIVED, Pdp::STATUS_CANCELLED])) {
            return response()->json(['error' => 'PDP verrouillé'], 423);
        }

        $request->validate([
            'file' => 'required|file|max:10240',
            'type' => 'required|in:caces,autorisation_conduite,habilitation,permis_feu,fds,plan_acces,convention_pret,autre',
            'label' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = uniqid('doc_').'.'.$ext;
        $relativeDir = "pdp/{$pdp->uuid}/uploads";
        $absoluteDir = storage_path('app/'.$relativeDir);

        if (! is_dir($absoluteDir)) {
            if (! @mkdir($absoluteDir, 0775, true) && ! is_dir($absoluteDir)) {
                \Log::error('Impossible de créer le dossier uploads SALTI', ['dir' => $absoluteDir]);
                return response()->json(['error' => 'Erreur serveur : impossible de créer le dossier de stockage.'], 500);
            }
        }
        if (! is_writable($absoluteDir)) {
            return response()->json(['error' => 'Erreur serveur : dossier non accessible en écriture.'], 500);
        }

        $absolutePath = $absoluteDir.'/'.$filename;
        $relativePath = $relativeDir.'/'.$filename;
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
        $size = $file->getSize();

        try {
            $file->move($absoluteDir, $filename);
        } catch (\Throwable $e) {
            \Log::error('Échec move() upload SALTI', ['err' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du fichier : '.$e->getMessage()], 500);
        }

        if (! file_exists($absolutePath) || filesize($absolutePath) === 0) {
            return response()->json(['error' => 'Le fichier n\'a pas pu être enregistré sur le serveur.'], 500);
        }

        $doc = $pdp->documents()->create([
            'type' => $request->input('type', 'autre'),
            'label' => $request->input('label'),
            'path' => $relativePath,
            'original_filename' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
            'uploaded_by' => 'salti',
        ]);

        $this->logAudit($pdp, 'uploaded_document_by_salti', $request, [
            'filename' => $doc->original_filename,
            'type' => $doc->type,
        ]);

        return response()->json([
            'id' => $doc->id,
            'filename' => $doc->original_filename,
            'type' => $doc->type,
            'label' => $doc->label,
            'size' => $doc->size,
            'uploaded_by' => $doc->uploaded_by,
            'download_url' => route('pdp.download.document', ['pdp' => $pdp, 'doc' => $doc->id]),
        ]);
    }

    /**
     * Suppression d'un document côté SALTI.
     * SALTI peut supprimer les documents qu'il a lui-même uploadés ET ceux du
     * presta (ex: doublon à nettoyer). Le presta est notifié dans l'audit log.
     */
    public function deleteDocument(Pdp $pdp, int $docId, Request $request)
    {
        $this->authorizePdp($pdp);

        if (in_array($pdp->status, [Pdp::STATUS_SIGNED, Pdp::STATUS_ARCHIVED])) {
            return response()->json(['error' => 'PDP signé/archivé : suppression interdite.'], 423);
        }

        $doc = $pdp->documents()->where('id', $docId)->first();
        if (! $doc) abort(404);

        $absolutePath = storage_path('app/'.$doc->path);
        if (file_exists($absolutePath)) @unlink($absolutePath);
        $doc->delete();

        $this->logAudit($pdp, 'deleted_document_by_salti', $request, [
            'filename' => $doc->original_filename,
            'uploaded_by' => $doc->uploaded_by,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Télécharge / Affiche un fichier uploadé (par le prestataire ou par SALTI).
     */
    public function downloadDocument(Pdp $pdp, int $docId, Request $request)
    {
        $this->authorizePdp($pdp);
        $doc = $pdp->documents()->where('id', $docId)->first();
        if (! $doc) abort(404, 'Document introuvable.');
        $absolutePath = storage_path('app/'.$doc->path);
        if (! file_exists($absolutePath)) {
            \Log::warning('Document DB existant mais fichier physique manquant', ['doc_id' => $docId, 'path' => $absolutePath]);
            abort(404, 'Le fichier joint a été perdu sur le serveur. Demandez au prestataire de le ré-uploader.');
        }
        return $this->fileOrDownload($absolutePath, $doc->original_filename, $request);
    }

    /**
     * Validation de la partie EE par SALTI (passage à "à signer").
     * Reste sur l'étape signatures (step=6) après validation.
     * BLOQUE si des erreurs critiques sont détectées par le PdpValidator.
     */
    public function validateByCAlti(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        $check = $this->validator->check($pdp);
        if (! $check['can_sign']) {
            return redirect()->route('pdp.edit', ['pdp' => $pdp, 'step' => 6])
                ->with('error', "Impossible de valider : {$check['errors_count']} erreur(s) critique(s) à corriger d'abord.");
        }

        $pdp->update([
            'status' => Pdp::STATUS_AWAITING_SIGNATURES,
            'validated_by_salti_at' => now(),
        ]);

        $this->logAudit($pdp, 'validated_by_salti', $request);

        return redirect()->route('pdp.edit', ['pdp' => $pdp, 'step' => 6])
            ->with('success', 'PDP validé. Vous pouvez maintenant signer.');
    }

    /**
     * Signature SALTI (depuis l'écran de signature).
     */
    public function signSalti(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        // Garde-fou : pas de signature tant qu'il y a des erreurs critiques
        $check = $this->validator->check($pdp);
        if (! $check['can_sign']) {
            return redirect()->route('pdp.edit', ['pdp' => $pdp, 'step' => 6])
                ->with('error', "Signature bloquée : {$check['errors_count']} erreur(s) critique(s) à corriger d'abord.");
        }

        $request->validate([
            'signature_data' => 'required|string',
            'signature_fonction' => 'required|string|max:255',
        ]);

        $data = $pdp->data;
        $data['signature_salti'] = $request->input('signature_data');
        $data['signature_salti_fonction'] = $request->input('signature_fonction');

        $pdp->update([
            'data' => $data,
            'signed_by_salti_at' => now(),
        ]);

        $this->logAudit($pdp, 'signed_by_salti', $request);
        $this->checkAllSigned($pdp);

        return redirect()->route('pdp.edit', ['pdp' => $pdp, 'step' => 6])
            ->with('success', 'Signature SALTI enregistrée.');
    }

    /**
     * Signature EE en mode présentiel : SALTI passe l'appareil au prestataire,
     * qui signe directement sur la même session.
     * Vérifie d'abord que SALTI a déjà signé (workflow normal en présentiel).
     */
    public function signEePresentiel(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        if ($pdp->mode !== Pdp::MODE_PRESENTIEL) {
            abort(400, 'Cette action est réservée au mode présentiel.');
        }

        // Garde-fou : pas de signature tant qu'il y a des erreurs critiques
        $check = $this->validator->check($pdp);
        if (! $check['can_sign']) {
            return redirect()->route('pdp.edit', ['pdp' => $pdp, 'step' => 6])
                ->with('error', "Signature bloquée : {$check['errors_count']} erreur(s) critique(s) à corriger d'abord.");
        }

        $request->validate([
            'signature_data' => 'required|string',
            'signature_fonction' => 'required|string|max:255',
            'signature_nom' => 'required|string|max:255',
        ]);

        $data = $pdp->data;
        $data['signature_ee'] = $request->input('signature_data');
        $data['signature_ee_fonction'] = $request->input('signature_fonction');
        // En présentiel, on capture aussi le nom du signataire EE
        $data['ee']['responsable_prestations'] = $request->input('signature_nom');

        // Auto-report sur le Permis feu (signature de l'employeur + date de délivrance)
        if (! empty($data['documents_remis_ee']['permis_feu'])) {
            $data['permis_feu']['signed_by_employer'] = $request->input('signature_data');
            if (empty($data['permis_feu']['date_delivrance'])) {
                $data['permis_feu']['date_delivrance'] = now()->toDateString();
            }
        }

        $pdp->update([
            'data' => $data,
            'signed_by_prestataire_at' => now(),
        ]);

        $this->logAudit($pdp, 'signed_by_prestataire_presentiel', $request);
        $this->checkAllSigned($pdp);

        return redirect()->route('pdp.edit', ['pdp' => $pdp, 'step' => 6])
            ->with('success', 'Signature de l\'entreprise extérieure enregistrée. PDP finalisé !');
    }

    /**
     * Si toutes les signatures sont là, on marque le PDP comme signé et on génère le PDF final.
     * Logique déléguée au modèle pour pouvoir l'appeler depuis le contrôleur prestataire aussi.
     */
    private function checkAllSigned(Pdp $pdp): void
    {
        $pdp->finalizeIfBothSigned($this->generator);
    }

    /**
     * Télécharge le PDF de calibration (croix rouges aux coordonnées du mapping).
     * Réservé au QSE central.
     */
    public function calibrationPdf(): BinaryFileResponse
    {
        if (! Auth::user()->isQseAdmin()) {
            abort(403, 'Réservé au compte QSE central.');
        }

        $relativePath = $this->legacyGenerator->generateCalibrationPdf();
        $absolutePath = storage_path('app/'.$relativePath);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="calibration-mapping.pdf"',
        ]);
    }

    /**
     * Met à jour OU crée un intervenant (multi-habilitations) sur un PDP.
     * Permet à SALTI de corriger les erreurs du prestataire (date mal saisie).
     *
     * Accepte deux formats :
     *  - Nouveau (multi) : { id, nom_prenom, habilitations: [{code, label, validity}, …] }
     *  - Legacy (single) : { id, nom_prenom, habilitation, habilitation_validity }
     */
    public function upsertIntervenant(Pdp $pdp, Request $request)
    {
        $this->authorizePdp($pdp);

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:pdp_intervenants,id',
            'nom_prenom' => 'required|string|max:255',
            'habilitation' => 'nullable|string|max:255',
            'habilitation_validity' => 'nullable|date',
            'habilitations' => 'nullable|array',
            'habilitations.*.code' => 'nullable|string|max:50',
            'habilitations.*.label' => 'nullable|string|max:255',
            'habilitations.*.validity' => 'nullable|date',
        ]);

        // Normalise le payload habilitations (multi) avec fallback sur le format legacy
        $habs = [];
        if (! empty($validated['habilitations']) && is_array($validated['habilitations'])) {
            foreach ($validated['habilitations'] as $h) {
                $label = trim($h['label'] ?? '');
                if ($label === '') continue;
                $habs[] = [
                    'code' => $h['code'] ?: null,
                    'label' => $label,
                    'validity' => ! empty($h['validity']) ? $h['validity'] : null,
                ];
            }
        } elseif (! empty($validated['habilitation'])) {
            $habs[] = [
                'code' => null,
                'label' => $validated['habilitation'],
                'validity' => $validated['habilitation_validity'] ?? null,
            ];
        }
        $primary = $habs[0] ?? null;

        $payload = [
            'nom_prenom' => $validated['nom_prenom'],
            'habilitation' => $primary['label'] ?? null,            // legacy column (compat)
            'habilitation_validity' => $primary['validity'] ?? null,// legacy column (compat)
            'habilitations' => $habs ?: null,                       // nouveau JSON
        ];

        if (! empty($validated['id'])) {
            $iv = $pdp->intervenants()->where('id', $validated['id'])->firstOrFail();
            $iv->update($payload);
        } else {
            $iv = $pdp->intervenants()->create($payload);
        }

        $this->logAudit($pdp, 'intervenant_updated_by_salti', $request, [
            'id' => $iv->id,
            'nom_prenom' => $iv->nom_prenom,
            'nb_habilitations' => count($habs),
        ]);

        return response()->json([
            'id' => $iv->id,
            'nom_prenom' => $iv->nom_prenom,
            'habilitations' => $habs,
        ]);
    }

    /**
     * Supprime un intervenant (par exemple si sa date d'habilitation est expirée).
     */
    public function deleteIntervenant(Pdp $pdp, int $intervenantId, Request $request)
    {
        $this->authorizePdp($pdp);

        $iv = $pdp->intervenants()->where('id', $intervenantId)->firstOrFail();
        $name = $iv->nom_prenom;
        $iv->delete();

        $this->logAudit($pdp, 'intervenant_removed_by_salti', $request, [
            'id' => $intervenantId,
            'nom_prenom' => $name,
        ]);

        return response()->json(['ok' => true, 'message' => "$name a été retiré du PDP"]);
    }

    /**
     * Annulation d'un PDP (soft : status passe à "cancelled" mais on garde la trace).
     */
    public function cancel(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        if (in_array($pdp->status, [Pdp::STATUS_SIGNED, Pdp::STATUS_ARCHIVED])) {
            return back()->with('error', 'Impossible d\'annuler un PDP déjà signé. Utilisez "Réouvrir" pour modifications.');
        }

        $pdp->update([
            'status' => Pdp::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_reason' => $request->input('reason', 'Annulé par '.Auth::user()->name),
        ]);

        $this->logAudit($pdp, 'cancelled', $request, ['reason' => $pdp->cancelled_reason]);

        return redirect()->route('dashboard')->with('success', 'PDP annulé.');
    }

    /**
     * Suppression définitive d'un PDP. RÉSERVÉ AU QSE CENTRAL.
     */
    public function destroy(Pdp $pdp, Request $request): RedirectResponse
    {
        if (! Auth::user()->isQseAdmin()) {
            abort(403, 'Suppression réservée au compte QSE central.');
        }

        $uuid = $pdp->uuid;
        $pdp->delete();

        return redirect()->route('dashboard')->with('success', "PDP {$uuid} supprimé définitivement.");
    }

    /**
     * Réouvrir un PDP signé pour permettre des corrections (passe en "corrections_requested").
     * Réservé au QSE central — laisse une trace dans l'audit log.
     * Les signatures précédentes sont effacées (il faudra re-signer).
     */
    public function reopen(Pdp $pdp, Request $request): RedirectResponse
    {
        if (! Auth::user()->isQseAdmin()) {
            abort(403, 'Réouverture réservée au compte QSE central.');
        }

        $data = $pdp->data;
        $data['signature_salti'] = null;
        $data['signature_ee'] = null;

        $pdp->update([
            'status' => Pdp::STATUS_DRAFT,
            'signed_by_salti_at' => null,
            'signed_by_prestataire_at' => null,
            'validated_by_salti_at' => null,
            'final_pdf_path' => null,
            'final_pdf_sha256' => null,
            'data' => $data,
        ]);

        $this->logAudit($pdp, 'reopened_by_qse', $request, [
            'reason' => $request->input('reason', 'Réouverture pour correction'),
        ]);

        return redirect()->route('pdp.edit', $pdp)->with('success', 'PDP rouvert pour modification. Les signatures précédentes ont été effacées.');
    }

    /**
     * Vérifie que l'utilisateur a le droit d'accéder à ce PDP.
     * Compte d'agence → uniquement les PDP de son agence.
     * Compte QSE central → tous.
     *
     * ⚠ Cast en int explicite : agency_id peut être renvoyé en string par PDO
     * selon la config, ce qui faisait échouer la comparaison stricte (!==).
     */
    private function authorizePdp(Pdp $pdp): void
    {
        $user = Auth::user();
        if ($user->isQseAdmin()) {
            return;
        }
        if ((int) $pdp->agency_id !== (int) $user->id) {
            abort(403, 'Accès refusé : ce PDP appartient à une autre agence.');
        }
    }

    /**
     * Helper pour logger toute action sur un PDP.
     */
    private function logAudit(Pdp $pdp, string $action, Request $request, array $payload = []): void
    {
        $user = Auth::user();
        $pdp->auditLogs()->create([
            'actor' => ($user->role === 'qse_admin' ? 'qse_admin' : 'agency').':'.$user->id,
            'action' => $action,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
