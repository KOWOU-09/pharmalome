<?php

namespace Tests\Feature;

use App\Models\Commande;
use App\Models\Ordonnance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_delivered_pickup_and_validated_prescription_counts(): void
    {
        $delivered = Commande::create(['user_id' => 1, 'statut' => 'livree']);
        Commande::create(['user_id' => 2, 'statut' => 'retiree']);
        Commande::create(['user_id' => 3, 'statut' => 'en_attente']);

        Ordonnance::create(['commande_id' => $delivered->id, 'statut' => 'validee']);
        Ordonnance::create([
            'commande_id' => Commande::query()->where('user_id', 2)->value('id'),
            'statut' => 'en_attente',
        ]);

        $this->getJson('/api/public/stats')
            ->assertForbidden();

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/public/stats')
            ->assertOk()
            ->assertExactJson([
                'commandes_livrees_ou_retirees' => 2,
                'ordonnances_validees' => 1,
            ]);
    }
}
