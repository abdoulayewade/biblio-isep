<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Livre extends Model
{
    // Le "pluralizer" de Laravel est anglais : on fixe le nom de table pour être sûr
    protected $table = 'livres';

    protected $fillable = ['titre', 'auteur', 'isbn', 'quantite', 'category_id'];

    // Un livre appartient à une catégorie (N-1)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Un livre a plusieurs emprunts (1-N)
    public function emprunts(): HasMany
    {
        return $this->hasMany(Emprunt::class);
    }

    public function empruntsEnCours(): HasMany
    {
        return $this->emprunts()->whereNull('date_retour_effective');
    }

    /**
     * Stock disponible, CALCULÉ (pas stocké en base) : $livre->disponible
     * Si la requête a fait withCount('empruntsEnCours'), on réutilise le compteur
     * => évite le problème N+1 dans les listes.
     */
    protected function disponible(): Attribute
    {
        return Attribute::get(function () {
            $enCours = $this->emprunts_en_cours_count ?? $this->empruntsEnCours()->count();

            return max(0, $this->quantite - $enCours);
        });
    }

    // Recherche par titre, auteur ou ISBN : Livre::recherche('clean')
    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($terme) {
            // whereLike = insensible à la casse sur MySQL ET PostgreSQL (ILIKE)
            $q->whereLike('titre', "%{$terme}%")
              ->orWhereLike('auteur', "%{$terme}%")
              ->orWhereLike('isbn', "%{$terme}%");
        });
    }
}
