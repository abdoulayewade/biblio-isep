<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpruntResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MesEmpruntsController extends Controller
{
    /**
     * GET /api/mes-emprunts?statut=en_cours|en_retard|rendus
     * Historique des emprunts de l'utilisateur connecté.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $statut = $request->query('statut');

        $emprunts = $request->user()
            ->emprunts()
            ->with(['livre' => fn ($q) => $q->with('category')->withCount('empruntsEnCours')])
            ->when($statut === 'en_cours', fn ($q) => $q->enCours())
            ->when($statut === 'en_retard', fn ($q) => $q->enRetard())
            ->when($statut === 'rendus', fn ($q) => $q->rendus())
            ->orderByRaw('date_retour_effective IS NOT NULL') // les emprunts en cours d'abord
            ->latest('date_emprunt')
            ->paginate(min(max($request->integer('per_page', 10), 1), 100))
            ->withQueryString();

        return EmpruntResource::collection($emprunts);
    }
}
