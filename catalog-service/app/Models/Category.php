<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'libelle',
    ];

    public function medicaments(): HasMany
    {
        return $this->hasMany(Medicament::class, 'categorie_id');
    }
}
