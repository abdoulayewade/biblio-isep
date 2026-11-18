<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format JSON d'un livre renvoyé par l'API.
 */
class LivreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'auteur' => $this->auteur,
            'isbn' => $this->isbn,
            'quantite' => $this->quantite,
            // stock calculé (quantite - emprunts en cours), voir Livre::disponible()
            'disponible' => $this->disponible,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
