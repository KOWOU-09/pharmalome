<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontCatalogTest extends TestCase
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

    public function test_admin_managed_products_and_categories_are_shown_in_the_storefront(): void
    {
        Http::fake([
            'http://catalog/api/categories' => Http::response([
                'data' => [['id' => 6, 'libelle' => 'Soins du quotidien']],
            ]),
            'http://catalog/api/medicaments*' => Http::response([
                'data' => [
                    'data' => [[
                        'id' => 91,
                        'categorie_id' => 6,
                        'categorie' => ['id' => 6, 'libelle' => 'Soins du quotidien'],
                        'nom' => 'Produit créé par l’administration',
                        'prix' => 5000,
                        'prix_promotion' => 4200,
                        'promotion' => true,
                        'quantite_stock' => 8,
                        'ordonnance_requise' => false,
                        'description' => 'Description du produit.',
                        'dosage' => 'Boîte de 10',
                        'laboratoire' => 'Laboratoire Togo',
                    ]],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 100,
                    'total' => 1,
                ],
            ]),
            '*' => Http::response([
                'utilisateurs_inscrits' => 1,
                'medicaments_catalogue' => 1,
                'commandes_livrees_ou_retirees' => 0,
                'ordonnances_validees' => 0,
            ]),
        ]);

        $this->get(route('catalogue', ['promotion' => 1]))
            ->assertOk()
            ->assertSee('Produit créé par l’administration')
            ->assertSee('Soins du quotidien')
            ->assertSee('4 200 FCFA')
            ->assertSee('5 000 FCFA')
            ->assertSee('data-price="4200"', false);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://catalog/api/medicaments?promotion=1&page=1&per_page=100'
            && $request->hasHeader('X-Internal-Key', 'test-internal-key'));
    }

    public function test_homepage_uses_categories_and_products_from_the_catalogue_service(): void
    {
        Http::fake([
            'http://catalog/api/categories' => Http::response([
                'data' => [['id' => 6, 'libelle' => 'Soins du quotidien']],
            ]),
            'http://catalog/api/medicaments*' => Http::response([
                'data' => [
                    'data' => [[
                        'id' => 91,
                        'categorie_id' => 6,
                        'categorie' => ['id' => 6, 'libelle' => 'Soins du quotidien'],
                        'nom' => 'Produit géré dans le catalogue',
                        'prix' => 1200,
                        'quantite_stock' => 8,
                        'ordonnance_requise' => false,
                        'promotion' => false,
                    ]],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 100,
                    'total' => 1,
                ],
            ]),
            '*' => Http::response([
                'utilisateurs_inscrits' => 1,
                'medicaments_catalogue' => 1,
                'commandes_livrees_ou_retirees' => 0,
                'ordonnances_validees' => 0,
            ]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Soins du quotidien')
            ->assertSee('Produit géré dans le catalogue')
            ->assertDontSee('Doliprane');
    }

    public function test_product_detail_uses_current_catalogue_stock_and_promotional_price(): void
    {
        $product = [
            'id' => 91,
            'categorie_id' => 6,
            'categorie' => ['id' => 6, 'libelle' => 'Soins du quotidien'],
            'nom' => 'Produit géré dans le catalogue',
            'prix' => 5000,
            'prix_promotion' => 4200,
            'promotion' => true,
            'quantite_stock' => 8,
            'ordonnance_requise' => false,
            'description' => 'Description du produit.',
            'dosage' => 'Boîte de 10',
            'laboratoire' => 'Laboratoire Togo',
        ];
        Http::fake([
            'http://catalog/api/medicaments/91' => Http::response(['data' => $product]),
            'http://catalog/api/medicaments*' => Http::response([
                'data' => [
                    'data' => [$product],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 4,
                    'total' => 1,
                ],
            ]),
        ]);

        $this->get(route('medicament.show', ['id' => 91]))
            ->assertOk()
            ->assertSee('Produit géré dans le catalogue')
            ->assertSee('4 200 FCFA')
            ->assertSee('5 000 FCFA')
            ->assertSee('data-price="4200"', false)
            ->assertSee('data-stock="8"', false);
    }

    public function test_admin_catalogue_lists_database_products_and_promotion_controls(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://catalog/api/categories' => Http::response([
                'data' => [['id' => 6, 'libelle' => 'Soins du quotidien', 'medicaments_count' => 1]],
            ]),
            'http://catalog/api/medicaments*' => Http::response([
                'data' => [
                    'data' => [[
                        'id' => 91,
                        'categorie_id' => 6,
                        'categorie' => ['id' => 6, 'libelle' => 'Soins du quotidien'],
                        'nom' => 'Produit géré dans le catalogue',
                        'prix' => 5000,
                        'prix_promotion' => 4200,
                        'promotion' => true,
                        'quantite_stock' => 8,
                        'ordonnance_requise' => false,
                        'description' => 'Description du produit.',
                        'dosage' => 'Boîte de 10',
                        'laboratoire' => 'Laboratoire Togo',
                    ]],
                    'current_page' => 2,
                    'last_page' => 3,
                    'per_page' => 20,
                    'total' => 1,
                ],
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('admin.catalogue', ['page' => 2]))
            ->assertOk()
            ->assertSee('Produit géré dans le catalogue')
            ->assertSee('name="prix_promotion"', false)
            ->assertSee('name="promotion"', false)
            ->assertSee('Page 2 sur 3')
            ->assertSee(route('admin.catalogue', ['page' => 1]))
            ->assertSee(route('admin.catalogue', ['page' => 3]));

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'http://catalog/api/medicaments?')
            && str_contains($request->url(), 'page=2')
            && str_contains($request->url(), 'per_page=20'));
    }
}
