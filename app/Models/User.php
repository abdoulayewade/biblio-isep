<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // HasApiTokens : jetons d'accès pour l'API (Laravel Sanctum)
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 'role' n'est volontairement PAS dans $fillable :
     * sinon un utilisateur pourrait s'inscrire en envoyant role=admin (faille de "mass assignment").
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Un user a plusieurs emprunts (1-N)
    public function emprunts(): HasMany
    {
        return $this->hasMany(Emprunt::class);
    }

    public function empruntsEnCours(): HasMany
    {
        return $this->emprunts()->whereNull('date_retour_effective');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
