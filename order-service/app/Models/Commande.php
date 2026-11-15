<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commande extends Model
{
    protected $fillable = [
        'user_id',
        'livreur_id',
        'statut',
        'mode_reception',
        'adresse_livraison',
        'sous_total',
        'frais_livraison',
        'total',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }

    public function ordonnance(): HasOne
    {
        return $this->hasOne(Ordonnance::class);
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
