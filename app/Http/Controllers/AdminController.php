<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\QseInterlocutor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Espace administration réservé au compte QSE central.
 * Permet de :
 *  - lister toutes les agences
 *  - créer une nouvelle agence (compte SALTI)
 *  - modifier les infos d'une agence
 *  - réinitialiser son mot de passe
 *  - désactiver / supprimer une agence
 */
class AdminController extends Controller
{
    /**
     * Garde-fou : seul le compte QSE central a accès à cet espace.
     */
    private function ensureQseAdmin(): void
    {
        if (! Auth::check() || ! Auth::user()->isQseAdmin()) {
            abort(403, 'Accès réservé au service QSE.');
        }
    }

    /**
     * Liste de toutes les agences (et compte QSE).
     */
    public function agencies(): View
    {
        $this->ensureQseAdmin();

        $agencies = User::orderByDesc('role')   // QSE en premier
            ->orderBy('city')
            ->orderBy('name')
            ->withCount('pdps')
            ->get();

        return view('admin.agencies.index', compact('agencies'));
    }

    /**
     * Formulaire de création d'une nouvelle agence.
     */
    public function createAgency(): View
    {
        $this->ensureQseAdmin();
        return view('admin.agencies.create');
    }

    /**
     * Création effective d'une nouvelle agence.
     * Génère un mot de passe initial qui sera affiché une seule fois au QSE pour transmission.
     */
    public function storeAgency(Request $request): RedirectResponse
    {
        $this->ensureQseAdmin();

        $validated = $request->validate([
            'city' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:8',
        ]);

        // Mot de passe : soit fourni par le QSE, soit généré aléatoirement
        $plainPassword = $validated['password'] ?? Str::password(12, true, true, false);

        $agency = User::create([
            'name' => 'Agence '.$validated['city'],
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'role' => User::ROLE_AGENCY,
            'city' => $validated['city'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        return redirect()->route('admin.agencies.index')
            ->with('success', "Agence créée :\n  Email : {$agency->email}\n  Mot de passe initial : {$plainPassword}\n\n⚠ Notez ce mot de passe — il ne sera plus affiché. Transmettez-le à l'agence par un canal sécurisé.");
    }

    /**
     * Formulaire d'édition d'une agence.
     */
    public function editAgency(User $agency): View
    {
        $this->ensureQseAdmin();
        return view('admin.agencies.edit', compact('agency'));
    }

    /**
     * Mise à jour des infos d'une agence (sans toucher au mot de passe).
     */
    public function updateAgency(User $agency, Request $request): RedirectResponse
    {
        $this->ensureQseAdmin();

        $validated = $request->validate([
            'city' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($agency->id)],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'require_otp_by_default' => 'sometimes|boolean',
        ]);

        $agency->update([
            'name' => 'Agence '.$validated['city'],
            'email' => $validated['email'],
            'city' => $validated['city'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'require_otp_by_default' => $request->boolean('require_otp_by_default'),
        ]);

        return redirect()->route('admin.agencies.index')->with('success', 'Agence mise à jour.');
    }

    /**
     * Reset du mot de passe d'une agence (affiche le nouveau mdp une fois).
     */
    public function resetPassword(User $agency, Request $request): RedirectResponse
    {
        $this->ensureQseAdmin();

        $request->validate([
            'password' => 'nullable|string|min:8',
        ]);

        $plainPassword = $request->input('password') ?: Str::password(12, true, true, false);
        $agency->update(['password' => Hash::make($plainPassword)]);

        return redirect()->route('admin.agencies.index')
            ->with('success', "Mot de passe réinitialisé pour {$agency->email} :\n  Nouveau mot de passe : {$plainPassword}\n\n⚠ Notez-le et transmettez-le à l'agence — il ne sera plus affiché.");
    }

    /**
     * Upload du Plan d'accès / circulation / zone d'attente pour cette agence.
     * Le fichier est stocké dans storage/app/agency-files/{agency_id}/access-plan.{ext}
     */
    public function uploadAgencyPlan(User $agency, Request $request): RedirectResponse
    {
        $this->ensureQseAdmin();

        $request->validate([
            'access_plan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10 MB
        ]);

        $file = $request->file('access_plan');
        $ext = $file->getClientOriginalExtension();
        $relativePath = "agency-files/{$agency->id}/access-plan.{$ext}";
        $absolutePath = storage_path('app/'.$relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        // Supprime l'ancien fichier si présent
        if ($agency->access_plan_path && file_exists(storage_path('app/'.$agency->access_plan_path))) {
            @unlink(storage_path('app/'.$agency->access_plan_path));
        }

        $file->move(dirname($absolutePath), basename($absolutePath));
        $agency->update([
            'access_plan_path' => $relativePath,
            'access_plan_filename' => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Plan d\'accès mis à jour pour '.$agency->name);
    }

    public function deleteAgencyPlan(User $agency): RedirectResponse
    {
        $this->ensureQseAdmin();

        if ($agency->access_plan_path && file_exists(storage_path('app/'.$agency->access_plan_path))) {
            @unlink(storage_path('app/'.$agency->access_plan_path));
        }
        $agency->update(['access_plan_path' => null, 'access_plan_filename' => null]);

        return back()->with('success', 'Plan d\'accès supprimé.');
    }

    public function downloadAgencyPlan(User $agency)
    {
        $this->ensureQseAdmin();
        if (! $agency->access_plan_path) abort(404);
        return response()->download(storage_path('app/'.$agency->access_plan_path), $agency->access_plan_filename);
    }

    /**
     * Page Settings : gestion des fichiers globaux (Permis feu, Convention de prêt).
     * Ces fichiers sont les MÊMES pour toutes les agences.
     */
    public function settings(): View
    {
        $this->ensureQseAdmin();
        return view('admin.settings.index', [
            'permisFeu' => [
                'path' => AppSetting::get('permis_feu_path'),
                'filename' => AppSetting::get('permis_feu_filename'),
            ],
            'conventionPret' => [
                'path' => AppSetting::get('convention_pret_path'),
                'filename' => AppSetting::get('convention_pret_filename'),
            ],
        ]);
    }

    public function uploadGlobalFile(Request $request, string $type): RedirectResponse
    {
        $this->ensureQseAdmin();

        $allowed = ['permis_feu', 'convention_pret'];
        if (! in_array($type, $allowed, true)) {
            abort(404);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $relativePath = "global-files/{$type}.{$ext}";
        $absolutePath = storage_path('app/'.$relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        $oldPath = AppSetting::get("{$type}_path");
        if ($oldPath && file_exists(storage_path('app/'.$oldPath))) {
            @unlink(storage_path('app/'.$oldPath));
        }

        $file->move(dirname($absolutePath), basename($absolutePath));
        AppSetting::set("{$type}_path", $relativePath);
        AppSetting::set("{$type}_filename", $file->getClientOriginalName());

        $labels = ['permis_feu' => 'Permis feu', 'convention_pret' => 'Convention de prêt de matériel'];

        return back()->with('success', $labels[$type].' mis à jour.');
    }

    public function deleteGlobalFile(string $type): RedirectResponse
    {
        $this->ensureQseAdmin();

        $oldPath = AppSetting::get("{$type}_path");
        if ($oldPath && file_exists(storage_path('app/'.$oldPath))) {
            @unlink(storage_path('app/'.$oldPath));
        }
        AppSetting::forget("{$type}_path");
        AppSetting::forget("{$type}_filename");

        return back()->with('success', 'Fichier supprimé.');
    }

    public function downloadGlobalFile(string $type)
    {
        // Public access (signed link not needed for now — agency users + QSE)
        $path = AppSetting::get("{$type}_path");
        $filename = AppSetting::get("{$type}_filename");
        if (! $path) abort(404);
        return response()->download(storage_path('app/'.$path), $filename ?: $type.'.pdf');
    }

    /**
     * Suppression d'une agence (avec confirmation).
     * ⚠ Cascade : supprime aussi tous les PDP rattachés à cette agence.
     */
    /**
     * Liste des interlocuteurs QSE — éditable par le QSE central.
     * Ces noms / téléphones apparaissent sur la page 1 du PDP.
     */
    public function interlocutors(): View
    {
        $this->ensureQseAdmin();
        return view('admin.interlocutors.index', [
            'interlocutors' => QseInterlocutor::orderBy('sort_order')->get(),
        ]);
    }

    public function storeInterlocutor(Request $request): RedirectResponse
    {
        $this->ensureQseAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'role' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'is_main' => 'sometimes|boolean',
        ]);
        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = (QseInterlocutor::max('sort_order') ?? 0) + 1;

        QseInterlocutor::create($data);

        return back()->with('success', 'Interlocuteur ajouté.');
    }

    public function updateInterlocutor(QseInterlocutor $interlocutor, Request $request): RedirectResponse
    {
        $this->ensureQseAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'role' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'is_main' => 'sometimes|boolean',
        ]);
        $data['is_main'] = $request->boolean('is_main');

        $interlocutor->update($data);

        return back()->with('success', 'Interlocuteur mis à jour.');
    }

    public function deleteInterlocutor(QseInterlocutor $interlocutor): RedirectResponse
    {
        $this->ensureQseAdmin();
        $interlocutor->delete();
        return back()->with('success', 'Interlocuteur supprimé.');
    }

    public function destroyAgency(User $agency): RedirectResponse
    {
        $this->ensureQseAdmin();

        if ($agency->isQseAdmin()) {
            return back()->with('error', 'Impossible de supprimer le compte QSE central.');
        }

        $email = $agency->email;
        $agency->delete();

        return redirect()->route('admin.agencies.index')->with('success', "Agence {$email} supprimée.");
    }
}
