<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpruntResource;
use App\Models\Category;
use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard : statistiques (globales pour l'admin, personnelles pour un lecteur).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return response()->json([
                'stats' => [
                    'livres' => Livre::count(),
                    'exemplaires' => (int) Livre::sum('quantite'),
                    'categories' => Category::count(),
                    'lecteurs' => User::where('role', 'lecteur')->count(),
                    'en_cours' => Emprunt::enCours()->count(),
                    'en_retard' => Emprunt::enRetard()->count(),
                ],
                'retards' => EmpruntResource::collection(
                    Emprunt::enRetard()
                        ->with(['user', 'livre' => fn ($q) => $q->withCount('empruntsEnCours')])
                        ->orderBy('date_retour_prevue')
                        ->take(5)
                        ->get()
                ),
            ]);
        }

        return response()->json([
            'stats' => [
                'en_cours' => $user->empruntsEnCours()->count(),
                'en_retard' => $user->emprunts()->enRetard()->count(),
                'total' => $user->emprunts()->count(),
                'max' => Emprunt::MAX_EN_COURS,
            ],
            'emprunts_en_cours' => EmpruntResource::collection(
                $user->empruntsEnCours()
                    ->with(['livre' => fn ($q) => $q->withCount('empruntsEnCours')])
                    ->orderBy('date_retour_prevue')
                    ->get()
            ),
        ]);
    }
}
