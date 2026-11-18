<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmpruntController;
use App\Http\Controllers\Api\LecteurController;
use App\Http\Controllers\Api\LivreController;
use App\Http\Controllers\Api\MesEmpruntsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web services REST (préfixe automatique : /api)
|--------------------------------------------------------------------------
| Authentification par jeton Sanctum : POST /api/login renvoie un "token",
| à envoyer ensuite dans l'en-tête  Authorization: Bearer <token>
| Toutes les réponses (y compris les erreurs) sont en JSON.
*/

// Routes publiques (limitées à 6 tentatives par minute contre le brute-force)
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
});

// Routes réservées aux utilisateurs connectés (lecteur ou admin)
Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/livres', [LivreController::class, 'index'])->name('livres.index');
    Route::get('/livres/{livre}', [LivreController::class, 'show'])->name('livres.show');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('/mes-emprunts', MesEmpruntsController::class)->name('emprunts.mes');

    // Routes ADMIN : /api/admin/... (403 si l'utilisateur n'est pas admin)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('livres', LivreController::class)->only(['store', 'update', 'destroy']);

        Route::get('lecteurs', [LecteurController::class, 'index'])->name('lecteurs.index');
        Route::post('lecteurs', [LecteurController::class, 'store'])->name('lecteurs.store');
        Route::get('lecteurs/{lecteur}', [LecteurController::class, 'show'])->name('lecteurs.show');

        Route::get('emprunts', [EmpruntController::class, 'index'])->name('emprunts.index');
        Route::post('emprunts', [EmpruntController::class, 'store'])->name('emprunts.store');
        Route::get('emprunts/{emprunt}', [EmpruntController::class, 'show'])->name('emprunts.show');
        Route::patch('emprunts/{emprunt}/retour', [EmpruntController::class, 'retour'])->name('emprunts.retour');
    });
});
