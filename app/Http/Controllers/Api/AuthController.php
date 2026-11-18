<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Authentification de l'API par jeton (Laravel Sanctum).
 * Le client envoie ensuite le jeton dans l'en-tête : Authorization: Bearer <jeton>
 */
class AuthController extends Controller
{
    /**
     * POST /api/register : inscription d'un lecteur (jamais admin, comme sur le site).
     */
    public function register(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        // 'role' n'est pas dans $fillable => toujours 'lecteur' (valeur par défaut en base)
        $user = User::create($donnees)->refresh();

        return response()->json([
            'message' => 'Inscription réussie.',
            'token' => $user->createToken($donnees['device_name'] ?? 'api')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/login : renvoie un jeton d'accès.
     */
    public function login(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $donnees['email'])->first();

        if (! $user || ! Hash::check($donnees['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $user->createToken($donnees['device_name'] ?? 'api')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * POST /api/logout : révoque le jeton utilisé pour cette requête.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * GET /api/me : l'utilisateur connecté.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->loadCount(['empruntsEnCours', 'emprunts']));
    }
}
