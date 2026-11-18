<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * GET /api/categories : liste (tout utilisateur connecté).
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::withCount('livres')->orderBy('nom')->get()
        );
    }

    /**
     * GET /api/categories/{category}
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('livres'));
    }

    /**
     * POST /api/admin/categories
     */
    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100', 'unique:categories,nom'],
        ]);

        return (new CategoryResource(Category::create($donnees)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/admin/categories/{category}
     */
    public function update(Request $request, Category $category): CategoryResource
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100', Rule::unique('categories', 'nom')->ignore($category->id)],
        ]);

        $category->update($donnees);

        return new CategoryResource($category);
    }

    /**
     * DELETE /api/admin/categories/{category}
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->livres()->exists()) {
            return response()->json([
                'message' => "Impossible de supprimer « {$category->nom} » : elle contient encore des livres.",
            ], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }
}
