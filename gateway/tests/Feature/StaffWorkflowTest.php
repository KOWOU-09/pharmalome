<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StaffWorkflowTest extends TestCase
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

    public function test_admin_user_listing_uses_the_signed_in_service_token(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://users/api/users' => Http::response([
                'data' => [[
                    'id' => 42,
                    'name' => 'Ama Mensah',
                    'email' => 'ama@example.com',
                    'role' => 'client',
                    'actif' => true,
                ]],
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('admin.utilisateurs'))
            ->assertOk()
            ->assertSee('Ama Mensah')
            ->assertSee(route('admin.utilisateurs.update', ['id' => 42]));

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/users'
            && $request->hasHeader('Authorization', 'Bearer admin-token')
            && $request->hasHeader('X-Internal-Key', 'test-internal-key'));
    }

    public function test_admin_user_filters_are_sent_to_the_user_service_and_preserved_in_the_form(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://users/api/users*' => Http::response([
                'data' => [[
                    'id' => 42,
                    'name' => 'Chantal AGBEKODO',
                    'email' => 'chantal@example.com',
                    'telephone' => '91581084',
                    'role' => 'client',
                    'actif' => true,
                ]],
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('admin.utilisateurs', ['search' => 'Chantal', 'role' => 'client']))
            ->assertOk()
            ->assertSee('Chantal AGBEKODO')
            ->assertSee('value="Chantal"', false)
            ->assertSee('Appliquer les filtres')
            ->assertSee('Tout afficher')
            ->assertSee('1 compte(s) affiché(s)');

        $usersRequest = Http::recorded(fn (HttpRequest $request): bool => parse_url($request->url(), PHP_URL_PATH) === '/api/users')
            ->first();
        $this->assertNotNull($usersRequest);
        parse_str($usersRequest[0]->toPsrRequest()->getUri()->getQuery(), $query);
        $this->assertSame('Chantal', $query['search'] ?? null);
        $this->assertSame('client', $query['role'] ?? null);
    }

    public function test_admin_can_save_a_user_role_and_active_state_through_the_user_service(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://users/api/users/42' => Http::response(['message' => 'Utilisateur mis à jour.']),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->patch(route('admin.utilisateurs.update', ['id' => 42]), [
            'role' => 'pharmacien',
            'actif' => '1',
        ])->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/users/42'
            && $request->method() === 'PATCH'
            && $request['role'] === 'pharmacien'
            && $request['actif'] === '1');
    }

    public function test_admin_can_create_staff_accounts_through_the_user_service(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://users/api/users' => Http::response([
                'message' => 'Invitation envoyée.',
            ], 201),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->post(route('admin.utilisateurs.store'), [
            'nom' => 'Agbeko',
            'prenom' => 'Kossi',
            'email' => 'kossi@example.com',
            'telephone' => '+228 90 00 00 00',
            'role' => 'livreur',
        ])->assertRedirect(route('admin.utilisateurs'))
            ->assertSessionHas('status', 'Invitation envoyée.');

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/users'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization')
            && $request['role'] === 'livreur');
    }

    public function test_admin_can_assign_an_order_only_to_an_active_courier(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://users/api/users/17' => Http::response([
                'data' => ['id' => 17, 'role' => 'livreur', 'actif' => true],
            ]),
            'http://orders/api/commandes/77/livreur' => Http::response([
                'message' => 'La mission de livraison a été attribuée.',
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->patch(route('admin.commandes.livreur', ['id' => 77]), ['livreur_id' => 17])
            ->assertRedirect()
            ->assertSessionHas('status', 'La mission de livraison a été attribuée.');

        Http::assertSent(fn ($request) => $request->url() === 'http://orders/api/commandes/77/livreur'
            && $request->method() === 'PATCH'
            && $request['livreur_id'] === 17
            && $request->hasHeader('X-Internal-Key', 'test-internal-key'));
    }

    public function test_pharmacist_restock_action_updates_the_catalogue_service(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 9,
                'name' => 'Pharmacien',
                'role' => 'pharmacien',
                'actif' => true,
                'pharmacien_verifie' => true,
            ]),
            'http://catalog/api/medicaments/12/stock/reapprovisionner' => Http::response([
                'message' => 'Stock réapprovisionné.',
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'pharmacist-token',
            'pharmalome_user' => ['id' => 9, 'name' => 'Pharmacien', 'role' => 'pharmacien', 'pharmacien_verifie' => true],
        ])->post(route('pharmacien.stock.reapprovisionner', ['id' => 12]), [
            'quantite' => 15,
        ])->assertRedirect()->assertSessionHas('status', 'Stock réapprovisionné.');

        Http::assertSent(fn ($request) => $request->url() === 'http://catalog/api/medicaments/12/stock/reapprovisionner'
            && $request['quantite'] === 15
            && $request->hasHeader('X-Internal-Key', 'test-internal-key'));
    }

    public function test_pharmacist_can_validate_a_pending_prescription(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 9,
                'name' => 'Pharmacien',
                'role' => 'pharmacien',
                'actif' => true,
                'pharmacien_verifie' => true,
            ]),
            'http://orders/api/ordonnances/5/valider' => Http::response([
                'message' => 'Ordonnance validée.',
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'pharmacist-token',
            'pharmalome_user' => ['id' => 9, 'name' => 'Pharmacien', 'role' => 'pharmacien', 'pharmacien_verifie' => true],
        ])->patch(route('pharmacien.ordonnances.valider', ['id' => 5]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Ordonnance validée.');

        Http::assertSent(fn ($request) => $request->url() === 'http://orders/api/ordonnances/5/valider'
            && $request->method() === 'PATCH'
            && $request['pharmacien_id'] === 9);
    }

    public function test_admin_order_status_changes_are_validated_and_forwarded(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://orders/api/commandes/77/statut' => Http::response(['message' => 'Statut mis à jour.']),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->patch(route('admin.commandes.statut', ['id' => 77]), ['statut' => 'en_livraison'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Statut mis à jour.');

        Http::assertSent(fn ($request) => $request->url() === 'http://orders/api/commandes/77/statut'
            && $request['statut'] === 'en_livraison');
    }

    public function test_admin_order_page_shows_delivery_assignment_for_active_couriers(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://orders/api/commandes' => Http::response([
                'data' => ['data' => [[
                    'id' => 77,
                    'user_id' => 42,
                    'statut' => 'en_livraison',
                    'mode_reception' => 'livraison',
                    'livreur_id' => null,
                    'total' => 2500,
                ]]],
            ]),
            'http://users/api/users*' => Http::response([
                'data' => [[
                    'id' => 17,
                    'name' => 'Kossi Agbeko',
                    'role' => 'livreur',
                    'actif' => true,
                ]],
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('admin.commandes'))
            ->assertOk()
            ->assertSee('Kossi Agbeko')
            ->assertSee(route('admin.commandes.livreur', ['id' => 77]));
    }
}
