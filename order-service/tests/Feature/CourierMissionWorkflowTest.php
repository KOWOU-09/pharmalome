<?php

namespace Tests\Feature;

use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierMissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.user_service.url' => 'http://users']);
        Http::fake(function ($request) {
            if ($request->hasHeader('Authorization', 'Bearer client-token')) {
                return Http::response([
                    'id' => 10,
                    'role' => 'client',
                    'actif' => true,
                ]);
            }

            if ($request->hasHeader('Authorization', 'Bearer other-courier-token')) {
                return Http::response([
                    'id' => 12,
                    'role' => 'livreur',
                    'actif' => true,
                ]);
            }

            return Http::response([
                'id' => 11,
                'role' => 'livreur',
                'actif' => true,
            ]);
        });
    }

    public function test_courier_missions_are_limited_to_the_assigned_courier(): void
    {
        $assigned = $this->createDelivery(11);
        $this->createDelivery(12);

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->withToken('courier-token')
            ->getJson('/api/livreur/commandes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assigned->id);
    }

    public function test_only_the_assigned_courier_can_complete_an_in_progress_delivery(): void
    {
        $commande = $this->createDelivery(11);

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->withToken('other-courier-token')
            ->patchJson("/api/livreur/commandes/{$commande->id}/statut")
            ->assertForbidden();

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->withToken('courier-token')
            ->patchJson("/api/livreur/commandes/{$commande->id}/statut")
            ->assertOk()
            ->assertJsonPath('data.statut', 'livree');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $commande->user_id,
            'message' => 'Votre commande a été livrée.',
        ]);
    }

    public function test_mission_routes_require_an_active_courier_token(): void
    {
        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/livreur/commandes')
            ->assertUnauthorized();

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->withToken('client-token')
            ->getJson('/api/livreur/commandes')
            ->assertForbidden();
    }

    public function test_only_an_active_delivery_order_can_be_assigned(): void
    {
        $commande = $this->createDelivery(11, 'validee');

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->patchJson("/api/commandes/{$commande->id}/livreur", ['livreur_id' => 11])
            ->assertUnprocessable();
    }

    private function createDelivery(int $courierId, string $status = 'en_livraison'): Commande
    {
        return Commande::create([
            'user_id' => 42,
            'livreur_id' => $courierId,
            'statut' => $status,
            'mode_reception' => 'livraison',
            'adresse_livraison' => 'Lomé',
            'sous_total' => 1500,
            'frais_livraison' => 1000,
            'total' => 2500,
        ]);
    }
}
