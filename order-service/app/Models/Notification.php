<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'lu',
    ];

    protected $casts = [
        'lu' => 'boolean',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'user_id', 'user_id');
    }
}
