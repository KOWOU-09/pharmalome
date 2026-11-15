<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.public_stats.users_url' => 'http://users',
            'services.public_stats.catalog_url' => 'http://catalog',
            'services.public_stats.orders_url' => 'http://orders',
        ]);
    }

    public function test_registration_creates_a_client_session_through_the_user_service(): void
    {
        Http::fake([
            'http://users/api/register' => Http::response([
                'token' => 'client-token',
                'user' => ['id' => 42, 'name' => 'Ama Mensah', 'email' => 'ama@example.com', 'role' => 'client'],
            ], 201),
        ]);

        $this->post(route('register.store'), [
            'nom' => 'Mensah',
            'prenom' => 'Ama',
            'email' => 'ama@example.com',
            'telephone' => '+228 90 00 00 00',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])
            ->assertRedirect(route('compte'))
            ->assertSessionHas('pharmalome_token', 'client-token')
            ->assertSessionHas('pharmalome_user.role', 'client');

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/register'
            && $request['prenom'] === 'Ama'
            && $request['nom'] === 'Mensah'
            && $request['password_confirmation'] === 'secure-pass-123');
    }

    public function test_login_stores_the_service_token_and_logout_revokes_it(): void
    {
        Http::fake([
            'http://users/api/login' => Http::response([
                'token' => 'client-token',
                'user' => ['id' => 42, 'name' => 'Ama Mensah', 'email' => 'ama@example.com', 'role' => 'client'],
            ]),
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'email' => 'ama@example.com',
                'role' => 'client',
                'actif' => true,
            ]),
            'http://users/api/logout' => Http::response(['message' => 'Déconnexion réussie.']),
        ]);

        $this->post(route('login.store'), [
            'email' => 'ama@example.com',
            'password' => 'secure-pass-123',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('pharmalome_token', 'client-token');

        $this->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionMissing('pharmalome_token')
            ->assertSessionMissing('pharmalome_user');

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/logout'
            && $request->hasHeader('Authorization', 'Bearer client-token'));
    }

    public function test_account_pages_require_authentication_and_staff_routes_check_roles(): void
    {
        $this->get(route('commandes'))->assertRedirect(route('login'));
        $this->get(route('compte'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'email' => 'ama@example.com',
                'role' => 'client',
                'actif' => true,
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->get(route('admin.dashboard'))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('access');
    }

    public function test_current_page_is_marked_in_the_navigation(): void
    {
        $this->fakeStorefrontCatalog();

        $response = $this->get(route('catalogue'))->assertOk();

        $this->assertMatchesRegularExpression(
            '/<a[^>]*class="is-active"[^>]*aria-current="page"/',
            $response->getContent(),
        );
    }

    public function test_password_fields_have_independent_visibility_toggles(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-toggle-password', false)
            ->assertSee('aria-label="Afficher le mot de passe"', false)
            ->assertSee('Créer un compte client');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-toggle-password', false)
            ->assertSee('vous connecte automatiquement');

        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'ama@example.com']))
            ->assertOk()
            ->assertSee('data-toggle-password', false);
    }

    public function test_admin_navigation_shows_admin_links_without_client_or_pharmacist_pages(): void
    {
        $this->fakeStorefrontCatalog();

        $response = $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('catalogue'))
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Produits')
            ->assertSee('Utilisateurs')
            ->assertSee('>Commandes</a>', false)
            ->assertSee('Mon compte');

        preg_match('/<header\b[^>]*>.*?<\/header>/s', $response->getContent(), $header);
        $this->assertNotEmpty($header);
        foreach (['Accueil', 'Nos produits', 'Promotions', 'Sur ordonnance', 'Panier', 'Espace pharmacien'] as $publicLink) {
            $this->assertStringNotContainsString($publicLink, $header[0]);
        }
    }

    public function test_admin_user_page_explains_how_clients_create_an_account(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 1,
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'actif' => true,
            ]),
            'http://users/api/users' => Http::response(['data' => []]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('admin.utilisateurs'))
            ->assertOk()
            ->assertSee('Ce formulaire sert uniquement aux pharmaciens, livreurs et administrateurs.')
            ->assertSee(route('register'));
    }

    public function test_admin_dashboard_links_directly_to_its_five_pages(): void
    {
        Cache::forget('pharmalome.public-stats.recent');
        Cache::forget('pharmalome.public-stats.last-known');
        Http::fake(function ($request) {
            if ($request->url() === 'http://users/api/me') {
                return Http::response([
                    'id' => 1,
                    'name' => 'Admin',
                    'role' => 'admin',
                    'actif' => true,
                ]);
            }

            return Http::response([
                'utilisateurs_inscrits' => 1,
                'medicaments_catalogue' => 1,
                'commandes_livrees_ou_retirees' => 0,
                'ordonnances_validees' => 0,
            ]);
        });

        $response = $this->withSession([
            'pharmalome_token' => 'admin-token',
            'pharmalome_user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin'],
        ])->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Accès rapide aux 5 espaces');

        preg_match('/<div class="staff-shortcuts admin-shortcuts">(.*?)<\\/div>/s', $response->getContent(), $shortcuts);
        $this->assertNotEmpty($shortcuts);
        foreach (['admin.dashboard', 'admin.catalogue', 'admin.utilisateurs', 'admin.commandes', 'compte'] as $routeName) {
            $this->assertStringContainsString(route($routeName), $shortcuts[1]);
        }
        $this->assertSame(5, preg_match_all('/<a\\b/', $shortcuts[1]));
        $this->assertStringNotContainsString('pharmacien.', $shortcuts[1]);
    }

    public function test_homepage_displays_the_storefront_sections_and_functional_product_actions(): void
    {
        $this->fakeStorefrontCatalog();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('MedExpress')
            ->assertSee('Achetez vos médicaments')
            ->assertSee('Catégories populaires')
            ->assertSee('Vitamines &amp; Compléments', false)
            ->assertSee('data-add-to-cart', false)
            ->assertSee('promotion=1');
    }

    public function test_catalog_filters_products_by_search_category_prescription_and_promotion(): void
    {
        $this->fakeStorefrontCatalog();

        $this->get(route('catalogue', ['search' => 'amoxicilline']))
            ->assertOk()
            ->assertSee('Amoxicilline')
            ->assertDontSee('Doliprane');

        $this->get(route('catalogue', ['categorie' => 'Santé Bébé & Enfant']))
            ->assertOk()
            ->assertSee('Sérum physiologique')
            ->assertDontSee('Doliprane');

        $this->get(route('catalogue', ['ordonnance' => 1]))
            ->assertOk()
            ->assertSee('Amoxicilline')
            ->assertDontSee('Doliprane');

        $this->get(route('catalogue', ['promotion' => 1]))
            ->assertOk()
            ->assertSee('Vitamine C 1000 mg')
            ->assertDontSee('Amoxicilline');
    }

    public function test_unknown_product_ids_return_a_not_found_page(): void
    {
        $this->fakeStorefrontCatalog();

        $this->get(route('medicament.show', ['id' => 999]))
            ->assertNotFound();
    }

    private function fakeStorefrontCatalog(): void
    {
        $products = [
            [
                'id' => 1,
                'nom' => 'Doliprane',
                'categorie_id' => 1,
                'categorie' => ['id' => 1, 'libelle' => 'Médicaments généraux'],
                'prix' => 2500,
                'quantite_stock' => 78,
                'ordonnance_requise' => false,
                'promotion' => false,
                'prix_promotion' => null,
                'description' => 'Un médicament courant.',
                'dosage' => '1000 mg',
                'laboratoire' => 'Sanofi',
            ],
            [
                'id' => 5,
                'nom' => 'Amoxicilline',
                'categorie_id' => 1,
                'categorie' => ['id' => 1, 'libelle' => 'Médicaments généraux'],
                'prix' => 4200,
                'quantite_stock' => 16,
                'ordonnance_requise' => true,
                'promotion' => false,
                'prix_promotion' => null,
                'description' => 'Sur ordonnance.',
                'dosage' => '250 mg',
                'laboratoire' => 'Lomé Santé',
            ],
            [
                'id' => 7,
                'nom' => 'Sérum physiologique',
                'categorie_id' => 2,
                'categorie' => ['id' => 2, 'libelle' => 'Santé Bébé & Enfant'],
                'prix' => 900,
                'quantite_stock' => 42,
                'ordonnance_requise' => false,
                'promotion' => false,
                'prix_promotion' => null,
                'description' => 'Solution stérile.',
                'dosage' => 'Boîte de 10',
                'laboratoire' => 'Santé Plus',
            ],
            [
                'id' => 3,
                'nom' => 'Vitamine C 1000 mg',
                'categorie_id' => 3,
                'categorie' => ['id' => 3, 'libelle' => 'Vitamines & Compléments'],
                'prix' => 3000,
                'quantite_stock' => 24,
                'ordonnance_requise' => false,
                'promotion' => true,
                'prix_promotion' => 2500,
                'description' => 'Complément alimentaire.',
                'dosage' => '1000 mg',
                'laboratoire' => 'GreenCare',
            ],
        ];

        Http::fake([
            'http://catalog/api/categories' => Http::response([
                'data' => [
                    ['id' => 1, 'libelle' => 'Médicaments généraux'],
                    ['id' => 2, 'libelle' => 'Santé Bébé & Enfant'],
                    ['id' => 3, 'libelle' => 'Vitamines & Compléments'],
                ],
            ]),
            'http://catalog/api/medicaments*' => function (HttpRequest $request) use ($products) {
                if (str_ends_with($request->url(), '/999')) {
                    return Http::response([], 404);
                }

                if (preg_match('~/api/medicaments/(\\d+)$~', $request->url(), $matches)) {
                    $product = collect($products)->firstWhere('id', (int) $matches[1]);

                    return $product ? Http::response(['data' => $product]) : Http::response([], 404);
                }

                $filtered = array_values(array_filter($products, function (array $product) use ($request): bool {
                    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                    return (! isset($query['search']) || str_contains(mb_strtolower($product['nom']), mb_strtolower($query['search'])))
                        && (! isset($query['categorie']) || $product['categorie']['libelle'] === $query['categorie'])
                        && (! isset($query['ordonnance']) || $product['ordonnance_requise'] === ($query['ordonnance'] === '1'))
                        && (! isset($query['promotion']) || ! ($query['promotion'] === '1') || $product['promotion']);
                }));

                return Http::response([
                    'data' => [
                        'data' => $filtered,
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 100,
                        'total' => count($filtered),
                    ],
                ]);
            },
            '*' => Http::response([
                'utilisateurs_inscrits' => 0,
                'medicaments_catalogue' => count($products),
                'commandes_livrees_ou_retirees' => 0,
                'ordonnances_validees' => 0,
            ]),
        ]);
    }

    public function test_account_page_shows_session_profile_and_navigation(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'email' => 'ama@example.com',
                'telephone' => '+228 90 00 00 00',
                'role' => 'client',
                'actif' => true,
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => [
                'id' => 42,
                'name' => 'Ama Mensah',
                'email' => 'ama@example.com',
                'telephone' => '+228 90 00 00 00',
                'role' => 'client',
            ],
        ])->get(route('compte'))
            ->assertOk()
            ->assertSee('Ama Mensah')
            ->assertSee('ama@example.com')
            ->assertSee(route('commandes'))
            ->assertSee('data-toggle-password', false);
    }

    public function test_profile_edits_are_saved_by_the_user_service(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'http://users/api/me' && $request->method() === 'GET') {
                return Http::response([
                    'id' => 42,
                    'name' => 'Ama Mensah',
                    'email' => 'ama@example.com',
                    'role' => 'client',
                    'actif' => true,
                ]);
            }

            if ($request->url() === 'http://users/api/me' && $request->method() === 'PATCH') {
                return Http::response([
                    'user' => [
                        'id' => 42,
                        'name' => 'Ama Mensah',
                        'nom' => 'Mensah',
                        'prenom' => 'Ama',
                        'email' => 'ama@example.com',
                        'telephone' => '+228 91 00 00 00',
                        'role' => 'client',
                    ],
                ]);
            }

            return Http::response([], 404);
        });

        $this->withSession([
            'pharmalome_token' => 'client-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->patch(route('compte.update'), [
            'nom' => 'Mensah',
            'prenom' => 'Ama',
            'email' => 'ama@example.com',
            'telephone' => '+228 91 00 00 00',
            'adresse' => 'Lomé',
        ])->assertRedirect()
            ->assertSessionHas('pharmalome_user.telephone', '+228 91 00 00 00');

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/me'
            && $request->method() === 'PATCH'
            && $request->hasHeader('Authorization')
            && $request['prenom'] === 'Ama');
    }

    public function test_password_changes_rotate_the_user_service_token(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 42,
                'name' => 'Ama Mensah',
                'role' => 'client',
                'actif' => true,
            ]),
            'http://users/api/me/password' => Http::response([
                'token' => 'rotated-token',
                'message' => 'Mot de passe modifié.',
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'old-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->patch(route('compte.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-secure-pass-123',
            'password_confirmation' => 'new-secure-pass-123',
        ])->assertRedirect()
            ->assertSessionHas('pharmalome_token', 'rotated-token');

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/me/password'
            && $request->method() === 'PATCH'
            && $request['current_password'] === 'old-password'
            && $request->hasHeader('Authorization'));
    }

    public function test_password_reset_requests_use_the_user_service(): void
    {
        Http::fake([
            'http://users/api/password/email' => Http::response(['message' => 'OK']),
            'http://users/api/password/reset' => Http::response(['message' => 'OK']),
        ]);

        $this->post(route('password.email'), ['email' => 'ama@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->post(route('password.update'), [
            'token' => 'one-time-token',
            'email' => 'ama@example.com',
            'password' => 'new-secure-pass-123',
            'password_confirmation' => 'new-secure-pass-123',
        ])->assertRedirect(route('login'));

        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/password/email');
        Http::assertSent(fn ($request) => $request->url() === 'http://users/api/password/reset'
            && $request['token'] === 'one-time-token');
    }

    public function test_unverified_pharmacists_cannot_use_pharmacy_routes_and_couriers_only_see_missions(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'http://users/api/me'
                && $request->hasHeader('Authorization', 'Bearer pharmacist-token')) {
                return Http::response([
                    'id' => 9,
                    'name' => 'Pharmacien',
                    'role' => 'pharmacien',
                    'actif' => true,
                    'pharmacien_verifie' => false,
                ]);
            }

            if ($request->url() === 'http://users/api/me') {
                return Http::response([
                    'id' => 17,
                    'name' => 'Livreur',
                    'role' => 'livreur',
                    'actif' => true,
                ]);
            }

            return Http::response(['data' => []]);
        });

        $this->withSession([
            'pharmalome_token' => 'pharmacist-token',
            'pharmalome_user' => ['id' => 9, 'name' => 'Pharmacien', 'role' => 'pharmacien'],
        ])->get(route('pharmacien.dashboard'))
            ->assertRedirect(route('compte'))
            ->assertSessionHasErrors('access');

        $this->withSession([
            'pharmalome_token' => 'courier-token',
            'pharmalome_user' => ['id' => 17, 'name' => 'Livreur', 'role' => 'livreur'],
        ])->get(route('livreur.dashboard'))
            ->assertOk()
            ->assertSee('Mes missions de livraison');

        $this->withSession([
            'pharmalome_token' => 'courier-token',
            'pharmalome_user' => ['id' => 17, 'name' => 'Livreur', 'role' => 'livreur'],
        ])->get(route('admin.utilisateurs'))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('access');
    }

    public function test_revoked_user_tokens_cannot_keep_stale_admin_permissions(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        $this->withSession([
            'pharmalome_token' => 'revoked-token',
            'pharmalome_user' => ['id' => 7, 'name' => 'Ancien admin', 'role' => 'admin'],
        ])->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('pharmalome_user')
            ->assertSessionMissing('pharmalome_token');
    }

    public function test_courier_can_complete_only_a_mission_via_the_order_service(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response([
                'id' => 17,
                'name' => 'Livreur',
                'role' => 'livreur',
                'actif' => true,
            ]),
            'http://orders/api/livreur/commandes/77/statut' => Http::response([
                'message' => 'La livraison a été marquée comme terminée.',
            ]),
        ]);

        $this->withSession([
            'pharmalome_token' => 'courier-token',
            'pharmalome_user' => ['id' => 17, 'name' => 'Livreur', 'role' => 'livreur'],
        ])->patch(route('livreur.missions.complete', ['id' => 77]))
            ->assertRedirect()
            ->assertSessionHas('status', 'La livraison a été marquée comme terminée.');

        Http::assertSent(fn ($request) => $request->url() === 'http://orders/api/livreur/commandes/77/statut'
            && $request->method() === 'PATCH'
            && $request->hasHeader('Authorization')
            && ! array_key_exists('livreur_id', $request->data()));
    }

    public function test_deactivated_clients_cannot_keep_using_authenticated_routes(): void
    {
        Http::fake([
            'http://users/api/me' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        $this->withSession([
            'pharmalome_token' => 'deactivated-token',
            'pharmalome_user' => ['id' => 42, 'name' => 'Ama Mensah', 'role' => 'client'],
        ])->get(route('commandes'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('pharmalome_user')
            ->assertSessionMissing('pharmalome_token');
    }
}
