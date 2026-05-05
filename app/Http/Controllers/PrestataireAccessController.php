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

        if (! in_array($pdp->status, [Pdp::STATUS_AWAITING_PRESTATAIRE, Pdp::STATUS_CORRECTIONS_REQUESTED])) {
            return response()->json(['error' => 'PDP non éditable dans cet état'], 423);
        }

        $payload = $request->input('data', []);

        // 1) Mise à jour des données JSON (clés autorisées côté prestataire)
        $allowed = ['ee', 'autres_risques', 'risques', 'documents_remis_salti'];
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
        if (isset($payload['intervenants']) && is_array($payload['intervenants'])) {
            // Stratégie simple : on supprime tout et on recrée. Acceptable car table petite (3-4 lignes max)
            $pdp->intervenants()->delete();
            foreach ($payload['intervenants'] as $iv) {
                if (empty($iv['nom_prenom']) && empty($iv['habilitation'])) {
                    continue;
                }
                $pdp->intervenants()->create([
                    'nom_prenom' => $iv['nom_prenom'] ?? '',
                    'habilitation' => $iv['habilitation'] ?? null,
                    'habilitation_validity' => ! empty($iv['habilitation_validity']) ? $iv['habilitation_validity'] : null,
                ]);
            }
        }

        return response()->json(['saved_at' => now()->format('H:i:s'), 'status' => 'ok']);
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
     */
    public function sign(string $token, Request $request): RedirectResponse
    {
        $pdp = $this->resolveToken($token);

        $request->validate([
            'signature_data' => 'required|string',
            'signature_fonction' => 'required|string|max:255',
        ]);

        $data = $pdp->data;
        $data['signature_ee'] = $request->input('signature_data');
        $data['signature_ee_fonction'] = $request->input('signature_fonction');

        $pdp->update([
            'data' => $data,
            'signed_by_prestataire_at' => now(),
        ]);

        $this->logAudit($pdp, 'signed_by_prestataire', $request);

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
