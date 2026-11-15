<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneCommande extends Model
{
    protected $table = 'ligne_commandes';

    public $timestamps = false;

    protected $fillable = [
        'commande_id',
        'medicament_id',
        'nom_medicament',
        'prix_unitaire',
        'quantite',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
