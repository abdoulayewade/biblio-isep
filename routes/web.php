<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EmpruntController;
use App\Http\Controllers\Admin\LecteurController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LivreController;
use App\Http\Controllers\MesEmpruntsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Page d'accueil : on envoie directement vers le tableau de bord (ou la connexion si non connecté)
Route::redirect('/', '/dashboard');

/*
|--------------------------------------------------------------------------
| Routes accessibles à tout utilisateur CONNECTÉ (lecteur ou admin)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/livres', [LivreController::class, 'index'])->name('livres.index');
    Route::get('/mes-emprunts', MesEmpruntsController::class)->name('emprunts.mes');

    // Profil (généré par Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Routes ADMIN : /admin/... protégées par le middleware 'admin'
    |----------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('livres', LivreController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);

        Route::get('lecteurs', [LecteurController::class, 'index'])->name('lecteurs.index');
        Route::get('lecteurs/create', [LecteurController::class, 'create'])->name('lecteurs.create');
        Route::post('lecteurs', [LecteurController::class, 'store'])->name('lecteurs.store');

        Route::get('emprunts', [EmpruntController::class, 'index'])->name('emprunts.index');
        Route::get('emprunts/create', [EmpruntController::class, 'create'])->name('emprunts.create');
        Route::post('emprunts', [EmpruntController::class, 'store'])->name('emprunts.store');
        Route::patch('emprunts/{emprunt}/retour', [EmpruntController::class, 'retour'])->name('emprunts.retour');
    });
});

// Connexion, inscription, mot de passe oublié... (générées par Breeze)
require __DIR__.'/auth.php';
