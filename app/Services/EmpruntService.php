<?php

namespace App\Services;

use App\Models\Emprunt;
use App\Models\Livre;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier des emprunts, partagées entre l'interface web et l'API
 * (une seule source de vérité : pas de règle dupliquée qui pourrait diverger).
 */
class EmpruntService
{
    /**
     * Crée un emprunt après vérification des règles métier.
     *
     * @throws ValidationException si une règle n'est pas respectée
     */
    public function emprunter(int $userId, int $livreId): Emprunt
    {
        // Transaction + verrou : si deux admins prêtent le dernier exemplaire au même moment,
        // le second attend que le premier ait fini, puis voit le stock à jour.
        return DB::transaction(function () use ($userId, $livreId) {
            $lecteur = User::findOrFail($userId);
            $livre = Livre::lockForUpdate()->findOrFail($livreId);

            // Règle 1 : le livre doit être disponible
            if ($livre->disponible <= 0) {
                throw ValidationException::withMessages([
                    'livre_id' => "« {$livre->titre} » n'est plus disponible (tous les exemplaires sont prêtés).",
                ]);
            }

            // Règle 2 : maximum MAX_EN_COURS emprunts en cours par lecteur
            if ($lecteur->empruntsEnCours()->count() >= Emprunt::MAX_EN_COURS) {
                throw ValidationException::withMessages([
                    'user_id' => "{$lecteur->name} a déjà ".Emprunt::MAX_EN_COURS.' emprunts en cours (maximum atteint).',
                ]);
            }

            // Règle 3 : pas deux fois le même livre en même temps
            if ($lecteur->empruntsEnCours()->where('livre_id', $livre->id)->exists()) {
                throw ValidationException::withMessages([
                    'livre_id' => "{$lecteur->name} a déjà ce livre en cours d'emprunt.",
                ]);
            }

            return Emprunt::create([
                'user_id' => $lecteur->id,
                'livre_id' => $livre->id,
                'date_emprunt' => today(),
                'date_retour_prevue' => today()->addDays(Emprunt::DUREE_JOURS),
            ]);
        });
    }
}
