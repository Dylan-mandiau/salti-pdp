<?php

use App\Http\Controllers\PdpController;
use App\Http\Controllers\PrestataireAccessController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Page d'accueil : redirige vers le dashboard si connecté, sinon vers login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('pdp.dashboard')
        : redirect()->route('login');
});

// ─── Espace SALTI (auth requise) ─────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [PdpController::class, 'dashboard'])->name('pdp.dashboard');
    Route::get('/dashboard', [PdpController::class, 'dashboard'])->name('dashboard'); // alias compat

    // Création d'un PDP
    Route::get('/pdp/new', [PdpController::class, 'chooseMode'])->name('pdp.choose-mode');
    Route::post('/pdp', [PdpController::class, 'store'])->name('pdp.store');

    // Édition / consultation d'un PDP
    Route::get('/pdp/{pdp}', [PdpController::class, 'edit'])->name('pdp.edit');
    Route::post('/pdp/{pdp}/auto-save', [PdpController::class, 'autoSave'])->name('pdp.auto-save');
    Route::post('/pdp/{pdp}/send', [PdpController::class, 'sendToPrestataire'])->name('pdp.send');
    Route::post('/pdp/{pdp}/validate', [PdpController::class, 'validateByCAlti'])->name('pdp.validate');
    Route::post('/pdp/{pdp}/sign-salti', [PdpController::class, 'signSalti'])->name('pdp.sign-salti');

    // Aperçu et téléchargement PDF
    Route::get('/pdp/{pdp}/preview', [PdpController::class, 'preview'])->name('pdp.preview');
    Route::get('/pdp/{pdp}/download', [PdpController::class, 'download'])->name('pdp.download');

    // Profil (Breeze par défaut)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Espace Prestataire (accès public via lien magique) ─────────────────
Route::get('/p/{token}', [PrestataireAccessController::class, 'show'])->name('prestataire.show');
Route::post('/p/{token}/save', [PrestataireAccessController::class, 'autoSave'])->name('prestataire.save');
Route::post('/p/{token}/submit', [PrestataireAccessController::class, 'submit'])->name('prestataire.submit');
Route::post('/p/{token}/sign', [PrestataireAccessController::class, 'sign'])->name('prestataire.sign');

require __DIR__.'/auth.php';
