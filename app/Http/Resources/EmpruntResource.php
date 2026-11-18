<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format JSON d'un emprunt renvoyé par l'API.
 */
class EmpruntResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_emprunt' => $this->date_emprunt?->toDateString(),
            'date_retour_prevue' => $this->date_retour_prevue?->toDateString(),
            'date_retour_effective' => $this->date_retour_effective?->toDateString(),
            // 'en_cours', 'en_retard' ou 'rendu' (calculé, voir Emprunt::statut())
            'statut' => $this->statut,
            'livre' => new LivreResource($this->whenLoaded('livre')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
