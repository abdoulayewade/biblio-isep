<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LivreResource;
use App\Models\Livre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LivreController extends Controller
{
    /**
     * GET /api/livres?q=...&categorie=...&disponible=1&per_page=10
     * Catalogue : accessible à tout utilisateur connecté.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $livres = Livre::query()
            ->with('category')
            ->withCount('empruntsEnCours') // évite le problème N+1 pour "disponible"
            ->recherche($request->query('q'))
            ->when($request->query('categorie'), fn ($q, $id) => $q->where('category_id', $id))
            // ?disponible=1 : seulement les livres dont au moins un exemplaire est en rayon
            ->when($request->boolean('disponible'), fn ($q) => $q->whereRaw(
                'livres.quantite > (select count(*) from emprunts where emprunts.livre_id = livres.id and emprunts.date_retour_effective is null)'
            ))
            ->orderBy('titre')
            ->paginate($this->parPage($request))
            ->withQueryString();

        return LivreResource::collection($livres);
    }

    /**
     * GET /api/livres/{livre}
     */
    public function show(Livre $livre): LivreResource
    {
        return new LivreResource($livre->load('category')->loadCount('empruntsEnCours'));
    }

    /**
     * POST /api/admin/livres
     */
    public function store(Request $request): JsonResponse
    {
        $livre = Livre::create($this->valider($request));

        return (new LivreResource($livre->load('category')->loadCount('empruntsEnCours')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/admin/livres/{livre}
     */
    public function update(Request $request, Livre $livre): LivreResource
    {
        $donnees = $this->valider($request, $livre);

        // On ne peut pas descendre la quantité sous le nombre d'exemplaires actuellement prêtés
        $enCours = $livre->empruntsEnCours()->count();
        if (isset($donnees['quantite']) && $donnees['quantite'] < $enCours) {
            throw ValidationException::withMessages([
                'quantite' => "Impossible : {$enCours} exemplaire(s) sont actuellement empruntés.",
            ]);
        }

        $livre->update($donnees);

        return new LivreResource($livre->load('category')->loadCount('empruntsEnCours'));
    }

    /**
     * DELETE /api/admin/livres/{livre}
     */
    public function destroy(Livre $livre): JsonResponse
    {
        if ($livre->emprunts()->exists()) {
            return response()->json([
                'message' => "Impossible de supprimer « {$livre->titre} » : il possède un historique d'emprunts.",
            ], 409);
        }

        $livre->delete();

        return response()->json(['message' => 'Livre supprimé.']);
    }

    private function valider(Request $request, ?Livre $livre = null): array
    {
        // En modification (PATCH), les champs non envoyés ne sont pas obligatoires
        $requis = $livre && $request->isMethod('patch') ? 'sometimes' : 'required';

        return $request->validate([
            'titre' => [$requis, 'string', 'max:200'],
            'auteur' => [$requis, 'string', 'max:150'],
            'isbn' => [$requis, 'string', 'max:20', Rule::unique('livres', 'isbn')->ignore($livre?->id)],
            'quantite' => [$requis, 'integer', 'min:1', 'max:1000'],
            'category_id' => [$requis, 'exists:categories,id'],
        ]);
    }

    private function parPage(Request $request): int
    {
        return min(max($request->integer('per_page', 10), 1), 100);
    }
}
