<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard', [
                'stats' => [
                    'livres' => Livre::count(),
                    'exemplaires' => (int) Livre::sum('quantite'),
                    'categories' => Category::count(),
                    'lecteurs' => User::where('role', 'lecteur')->count(),
                    'en_cours' => Emprunt::enCours()->count(),
                    'en_retard' => Emprunt::enRetard()->count(),
                ],
                'retards' => Emprunt::enRetard()
                    ->with(['user', 'livre'])
                    ->orderBy('date_retour_prevue')
                    ->take(5)
                    ->get(),
            ]);
        }

        return view('dashboard', [
            'stats' => [
                'en_cours' => $user->empruntsEnCours()->count(),
                'en_retard' => $user->emprunts()->enRetard()->count(),
                'total' => $user->emprunts()->count(),
                'max' => Emprunt::MAX_EN_COURS,
            ],
            'empruntsEnCours' => $user->empruntsEnCours()
                ->with('livre')
                ->orderBy('date_retour_prevue')
                ->get(),
        ]);
    }
}
