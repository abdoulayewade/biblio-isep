<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['nom'];

    // Une catégorie contient plusieurs livres (1-N)
    public function livres(): HasMany
    {
        return $this->hasMany(Livre::class);
    }
}
