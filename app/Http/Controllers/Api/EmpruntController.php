<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpruntResource;
use App\Models\Emprunt;
use App\Services\EmpruntService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Gestion des emprunts par l'administrateur.
 */
class EmpruntController extends Controller
{
    /**
     * GET /api/admin/emprunts?statut=en_cours|en_retard|rendus&q=...&user_id=...
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $statut = $request->query('statut');

        $emprunts = Emprunt::query()
            ->with(['user', 'livre' => fn ($q) => $q->withCount('empruntsEnCours')])
            ->when($statut === 'en_cours', fn ($q) => $q->enCours())
            ->when($statut === 'en_retard', fn ($q) => $q->enRetard())
            ->when($statut === 'rendus', fn ($q) => $q->rendus())
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->query('q'), function ($q, $terme) {
                $q->where(function ($q) use ($terme) {
                    $q->whereHas('user', fn ($u) => $u->whereLike('name', "%{$terme}%"))
                      ->orWhereHas('livre', fn ($l) => $l->whereLike('titre', "%{$terme}%"));
                });
            })
            ->latest('date_emprunt')
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return EmpruntResource::collection($emprunts);
    }

    /**
     * GET /api/admin/emprunts/{emprunt}
     */
    public function show(Emprunt $emprunt): EmpruntResource
    {
        return new EmpruntResource($emprunt->load(['user', 'livre.category']));
    }

    /**
     * POST /api/admin/emprunts  { "user_id": 1, "livre_id": 2 }
     * Erreur 422 si une règle métier n'est pas respectée.
     */
    public function store(Request $request, EmpruntService $service): JsonResponse
    {
        $donnees = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'livre_id' => ['required', 'integer', 'exists:livres,id'],
        ]);

        $emprunt = $service->emprunter($donnees['user_id'], $donnees['livre_id']);

        return (new EmpruntResource($emprunt->load(['user', 'livre.category'])))
            ->additional(['message' => 'Emprunt enregistré. Retour prévu dans '.Emprunt::DUREE_JOURS.' jours.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PATCH /api/admin/emprunts/{emprunt}/retour
     */
    public function retour(Emprunt $emprunt): JsonResponse
    {
        if ($emprunt->date_retour_effective) {
            return response()->json(['message' => 'Ce livre a déjà été rendu.'], 409);
        }

        $emprunt->update(['date_retour_effective' => today()]);

        return (new EmpruntResource($emprunt->load(['user', 'livre.category'])))
            ->additional(['message' => "Retour de « {$emprunt->livre->titre} » enregistré."])
            ->response();
    }
}
