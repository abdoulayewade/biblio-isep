<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpruntResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Password;

/**
 * Gestion des lecteurs par l'administrateur.
 */
class LecteurController extends Controller
{
    /**
     * GET /api/admin/lecteurs?q=...
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $lecteurs = User::where('role', 'lecteur')
            ->withCount(['empruntsEnCours', 'emprunts'])
            ->when($request->query('q'), function ($q, $terme) {
                $q->where(fn ($q) => $q->whereLike('name', "%{$terme}%")
                                       ->orWhereLike('email', "%{$terme}%"));
            })
            ->orderBy('name')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return UserResource::collection($lecteurs);
    }

    /**
     * GET /api/admin/lecteurs/{lecteur} : fiche + historique des emprunts.
     */
    public function show(User $lecteur): JsonResponse
    {
        abort_unless($lecteur->role === 'lecteur', 404);

        $emprunts = $lecteur->emprunts()
            ->with(['livre' => fn ($q) => $q->withCount('empruntsEnCours')])
            ->orderByRaw('date_retour_effective IS NOT NULL')
            ->latest('date_emprunt')
            ->get();

        return response()->json([
            'data' => new UserResource($lecteur->loadCount(['empruntsEnCours', 'emprunts'])),
            'emprunts' => EmpruntResource::collection($emprunts),
        ]);
    }

    /**
     * POST /api/admin/lecteurs : inscription "au guichet".
     */
    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // role = 'lecteur' par défaut (valeur par défaut de la colonne)
        $lecteur = User::create($donnees)->refresh();

        return (new UserResource($lecteur))->response()->setStatusCode(201);
    }
}
