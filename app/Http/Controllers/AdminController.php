<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
     * Suppression d'une agence (avec confirmation).
     * ⚠ Cascade : supprime aussi tous les PDP rattachés à cette agence.
     */
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
