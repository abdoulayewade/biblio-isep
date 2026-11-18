<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format JSON d'un utilisateur. Le mot de passe n'est jamais exposé.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'emprunts_en_cours' => $this->whenCounted('empruntsEnCours'),
            'emprunts_total' => $this->whenCounted('emprunts'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
