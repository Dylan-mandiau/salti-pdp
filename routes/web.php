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
    // Dashboard (le name "dashboard" sert pour la redirection Breeze post-login)
    Route::get('/dashboard', [PdpController::class, 'dashboard'])->name('dashboard');

    // Création d'un PDP
    Route::get('/pdp/new', [PdpController::class, 'chooseMode'])->name('pdp.choose-mode');
    Route::post('/pdp', [PdpController::class, 'store'])->name('pdp.store');

    // Édition / consultation d'un PDP
    Route::get('/pdp/{pdp}', [PdpController::class, 'edit'])->name('pdp.edit');
    Route::post('/pdp/{pdp}/auto-save', [PdpController::class, 'autoSave'])->name('pdp.auto-save');
    Route::post('/pdp/{pdp}/send', [PdpController::class, 'sendToPrestataire'])->name('pdp.send');
    Route::post('/pdp/{pdp}/validate', [PdpController::class, 'validateByCAlti'])->name('pdp.validate');
    Route::post('/pdp/{pdp}/sign-salti', [PdpController::class, 'signSalti'])->name('pdp.sign-salti');
    Route::post('/pdp/{pdp}/sign-ee-presentiel', [PdpController::class, 'signEePresentiel'])->name('pdp.sign-ee-presentiel');

    // Aperçu et téléchargement PDF
    Route::get('/pdp/{pdp}/preview', [PdpController::class, 'preview'])->name('pdp.preview');
    Route::get('/pdp/{pdp}/download', [PdpController::class, 'download'])->name('pdp.download');

    // Profil (Breeze par défaut)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Administration QSE (création / gestion des agences) ───────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/agencies', [App\Http\Controllers\AdminController::class, 'agencies'])->name('agencies.index');
        Route::get('/agencies/create', [App\Http\Controllers\AdminController::class, 'createAgency'])->name('agencies.create');
        Route::post('/agencies', [App\Http\Controllers\AdminController::class, 'storeAgency'])->name('agencies.store');
        Route::get('/agencies/{agency}/edit', [App\Http\Controllers\AdminController::class, 'editAgency'])->name('agencies.edit');
        Route::patch('/agencies/{agency}', [App\Http\Controllers\AdminController::class, 'updateAgency'])->name('agencies.update');
        Route::post('/agencies/{agency}/reset-password', [App\Http\Controllers\AdminController::class, 'resetPassword'])->name('agencies.reset-password');
        Route::delete('/agencies/{agency}', [App\Http\Controllers\AdminController::class, 'destroyAgency'])->name('agencies.destroy');
    });
});

// ─── Espace Prestataire (accès public via lien magique) ─────────────────
Route::get('/p/{token}', [PrestataireAccessController::class, 'show'])->name('prestataire.show');
Route::post('/p/{token}/save', [PrestataireAccessController::class, 'autoSave'])->name('prestataire.save');
Route::post('/p/{token}/submit', [PrestataireAccessController::class, 'submit'])->name('prestataire.submit');
Route::post('/p/{token}/sign', [PrestataireAccessController::class, 'sign'])->name('prestataire.sign');

require __DIR__.'/auth.php';
