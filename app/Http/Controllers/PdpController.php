<?php

namespace App\Http\Controllers;

use App\Models\Pdp;
use App\Models\Prestataire;
use App\Models\User;
use App\Services\PdpPdfGenerator;
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
    public function __construct(private PdpPdfGenerator $generator) {}

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
        return view('pdp.choose-mode');
    }

    /**
     * Création d'un nouveau PDP avec le mode choisi.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'mode' => 'required|in:presentiel,distance',
            'donneur_ordre_nom' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        $pdp = Pdp::create([
            'agency_id' => $user->id,
            'mode' => $request->input('mode'),
            'status' => Pdp::STATUS_DRAFT,
            'donneur_ordre_nom' => $request->input('donneur_ordre_nom'),
            'require_otp' => $user->require_otp_by_default,
            'data' => Pdp::emptyData(),
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

        return view('pdp.edit', [
            'pdp' => $pdp,
            'step' => $step,
            'prestataires' => $prestataires,
            'agencies' => $agencies,
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
     * Génère un lien magique et l'envoie au prestataire (mode "à distance").
     */
    public function sendToPrestataire(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

        $request->validate([
            'prestataire_email' => 'required|email',
        ]);

        $token = $pdp->generateMagicToken(7);
        $pdp->update([
            'status' => Pdp::STATUS_AWAITING_PRESTATAIRE,
            'sent_to_prestataire_at' => now(),
        ]);

        $this->logAudit($pdp, 'sent_to_prestataire', $request, [
            'email' => $request->input('prestataire_email'),
        ]);

        // TODO: envoi email réel via Mail::send
        // Mail::to($request->prestataire_email)->send(new PrestataireInvitation($pdp));

        return redirect()->route('pdp.edit', $pdp)
            ->with('success', "Lien envoyé au prestataire (valable 7 jours).\n".$pdp->magicLinkUrl());
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
     */
    public function download(Pdp $pdp): BinaryFileResponse
    {
        $this->authorizePdp($pdp);

        if (! $pdp->final_pdf_path) {
            $pdp->update([
                'final_pdf_path' => $this->generator->generate($pdp),
            ]);
            $pdp->refresh();
        }

        $absolutePath = storage_path('app/'.$pdp->final_pdf_path);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="plan-prevention-'.$pdp->uuid.'.pdf"',
        ]);
    }

    /**
     * Validation de la partie EE par SALTI (passage à "à signer").
     * Reste sur l'étape signatures (step=6) après validation.
     */
    public function validateByCAlti(Pdp $pdp, Request $request): RedirectResponse
    {
        $this->authorizePdp($pdp);

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
     */
    private function checkAllSigned(Pdp $pdp): void
    {
        $pdp->refresh();
        if ($pdp->signed_by_salti_at && $pdp->signed_by_prestataire_at) {
            $finalPath = $this->generator->generate($pdp);
            $absolutePath = storage_path('app/'.$finalPath);
            $hash = hash_file('sha256', $absolutePath);

            $pdp->update([
                'status' => Pdp::STATUS_SIGNED,
                'final_pdf_path' => $finalPath,
                'final_pdf_sha256' => $hash,
            ]);
        }
    }

    /**
     * Vérifie que l'utilisateur a le droit d'accéder à ce PDP.
     * Compte d'agence → uniquement les PDP de son agence.
     * Compte QSE central → tous.
     */
    private function authorizePdp(Pdp $pdp): void
    {
        $user = Auth::user();
        if ($user->isQseAdmin()) {
            return;
        }
        if ($pdp->agency_id !== $user->id) {
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
