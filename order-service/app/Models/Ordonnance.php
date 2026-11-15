<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ordonnance extends Model
{
    protected $fillable = [
        'commande_id',
        'fichier',
        'statut',
        'motif_refus',
        'pharmacien_id',
        'validee_le',
    ];

    protected $casts = [
        'validee_le' => 'datetime',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
