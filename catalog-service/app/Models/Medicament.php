<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medicament extends Model
{
    protected $fillable = [
        'categorie_id',
        'nom',
        'dosage',
        'laboratoire',
        'description',
        'prix',
        'quantite_stock',
        'seuil_alerte',
        'date_peremption',
        'ordonnance_requise',
        'promotion',
        'prix_promotion',
        'photo',
    ];

    protected $casts = [
        'ordonnance_requise' => 'boolean',
        'promotion' => 'boolean',
        'prix_promotion' => 'integer',
        'date_peremption' => 'date',
    ];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_id');
    }
}
