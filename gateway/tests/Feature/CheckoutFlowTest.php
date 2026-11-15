<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.public_stats.users_url' => 'http://users',
            'services.public_stats.catalog_url' => 'http://catalog',
            'services.public_stats.orders_url' => 'http://orders',
            'services.public_stats.internal_key' => 'test-internal-key',
        ]);
    }

    public function test_checkout_validates_stock_and_uses_catalogue_prices_before_creating_an_order(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'role' => 'client',
                'actif' => true,
            ]),
            'http://catalog/api/medicaments/verifier' => Http::response([
                'ok' => true,
                'data' => [[
                    'id' => 2,
                    'nom' => 'Paracétamol',
                    'prix' => 1500,
                    'ordonnance_requise' => false,
                    'ok' => true,
                    'message' => 'Disponible.',
                ]],
            ]),
            'http://orders/api/commandes' => Http::response([
                'message' => 'Commande enregistrée.',
                'data' => ['id' => 99],
            ], 201),
        ]);

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->post(route('checkout.store'), [
            'items' => json_encode([['id' => 2, 'quantity' => 2, 'price' => 1]]),
            'mode_reception' => 'retrait',
        ])
            ->assertRedirect(route('commandes'))
            ->assertSessionHas('clear_cart', true)
            ->assertSessionHas('status', 'Votre commande #99 a bien été enregistrée.');

        Http::assertSent(fn ($request) => $request->url() === 'http://orders/api/commandes'
            && $request['user_id'] === 42
            && $request['items'][0]['nom'] === 'Paracétamol'
            && $request['items'][0]['prix'] === 1500
            && $request['items'][0]['quantite'] === 2
            && $request->hasHeader('X-Internal-Key', 'test-internal-key'));
    }

    public function test_checkout_requires_an_ordinance_when_a_catalogue_item_requires_one(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'role' => 'client',
                'actif' => true,
            ]),
            'http://catalog/api/medicaments/verifier' => Http::response([
                'ok' => true,
                'data' => [[
                    'id' => 5,
                    'nom' => 'Amoxicilline',
                    'prix' => 4200,
                    'ordonnance_requise' => true,
                    'ok' => true,
                    'message' => 'Disponible.',
                ]],
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->from(route('checkout'))->post(route('checkout.store'), [
            'items' => json_encode([['id' => 5, 'quantity' => 1]]),
            'mode_reception' => 'retrait',
        ])
            ->assertRedirect(route('checkout'))
            ->assertSessionHasErrors('ordonnance');

        Http::assertSentCount(2);
    }

    public function test_checkout_rejects_empty_or_invalid_cart_data(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'role' => 'client',
                'actif' => true,
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->from(route('checkout'))->post(route('checkout.store'), [
            'items' => '[]',
            'mode_reception' => 'retrait',
        ])
            ->assertRedirect(route('checkout'))
            ->assertSessionHasErrors('items');

        Http::assertSentCount(1);
    }

    public function test_delivery_checkout_requires_a_valid_address(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'role' => 'client',
                'actif' => true,
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->from(route('checkout'))->post(route('checkout.store'), [
            'items' => json_encode([['id' => 2, 'quantity' => 1]]),
            'mode_reception' => 'livraison',
            'adresse_livraison' => '',
        ])
            ->assertRedirect(route('checkout'))
            ->assertSessionHasErrors('adresse_livraison');
    }
}
