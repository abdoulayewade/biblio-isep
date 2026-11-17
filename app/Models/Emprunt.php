<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emprunt extends Model
{
    // Règles métier centralisées : une seule ligne à changer si le prof demande autre chose
    public const DUREE_JOURS = 14;
    public const MAX_EN_COURS = 3;

    protected $table = 'emprunts';

    protected $fillable = [
        'user_id',
        'livre_id',
        'date_emprunt',
        'date_retour_prevue',
        'date_retour_effective',
    ];

    protected function casts(): array
    {
        return [
            'date_emprunt' => 'date',
            'date_retour_prevue' => 'date',
            'date_retour_effective' => 'date',
        ];
    }

    // Un emprunt appartient à un user (N-1)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un emprunt concerne un livre (N-1)
    public function livre(): BelongsTo
    {
        return $this->belongsTo(Livre::class);
    }

    // Emprunt::enCours()
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereNull('date_retour_effective');
    }

    // Emprunt::enRetard()
    public function scopeEnRetard(Builder $query): Builder
    {
        return $query->whereNull('date_retour_effective')
                     ->whereDate('date_retour_prevue', '<', now()->toDateString());
    }

    // Emprunt::rendus()
    public function scopeRendus(Builder $query): Builder
    {
        return $query->whereNotNull('date_retour_effective');
    }

    // $emprunt->est_en_retard
    protected function estEnRetard(): Attribute
    {
        return Attribute::get(fn () =>
            is_null($this->date_retour_effective)
            && $this->date_retour_prevue->lt(now()->startOfDay())
        );
    }

    // $emprunt->statut : 'rendu', 'en_retard' ou 'en_cours'
    protected function statut(): Attribute
    {
        return Attribute::get(function () {
            if (! is_null($this->date_retour_effective)) {
                return 'rendu';
            }

            return $this->est_en_retard ? 'en_retard' : 'en_cours';
        });
    }
}
