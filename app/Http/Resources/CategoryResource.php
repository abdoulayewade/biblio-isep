<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format JSON d'une catégorie renvoyé par l'API.
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            // présent seulement si la requête a fait withCount('livres')
            'nombre_livres' => $this->whenCounted('livres'),
        ];
    }
}
