<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicStatsTest extends TestCase
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

    public function test_it_aggregates_and_caches_counters_and_returns_not_modified_for_a_matching_etag(): void
    {
        Http::fake([
            'http://users/api/public/stats' => Http::response(['utilisateurs_inscrits' => 12]),
            'http://catalog/api/public/stats' => Http::response(['medicaments_catalogue' => 34]),
            'http://orders/api/public/stats' => Http::response([
                'commandes_livrees_ou_retirees' => 56,
                'ordonnances_validees' => 78,
            ]),
        ]);

        $response = $this->getJson('/api/stats-publiques')
            ->assertOk()
            ->assertExactJson([
                'utilisateurs_inscrits' => 12,
                'medicaments_catalogue' => 34,
                'commandes_livrees_ou_retirees' => 56,
                'ordonnances_validees' => 78,
            ]);

        $etag = $response->headers->get('ETag');

        $this->getJson('/api/stats-publiques', ['If-None-Match' => $etag])
            ->assertStatus(304);

        Http::assertSentCount(3);
    }

    public function test_it_returns_last_known_counts_when_services_are_unavailable(): void
    {
        $lastKnown = [
            'utilisateurs_inscrits' => 1200,
            'medicaments_catalogue' => 340,
            'commandes_livrees_ou_retirees' => 560,
            'ordonnances_validees' => 780,
        ];
        Cache::put('pharmalome.public-stats.last-known', $lastKnown, now()->addDay());
        Http::fake(fn () => Http::response(['message' => 'indisponible'], 503));

        $this->getJson('/api/stats-publiques')
            ->assertOk()
            ->assertExactJson($lastKnown);
    }

    public function test_homepage_renders_live_counts_in_the_server_generated_html(): void
    {
        Http::fake([
            'http://users/api/public/stats' => Http::response(['utilisateurs_inscrits' => 2347]),
            'http://catalog/api/public/stats' => Http::response(['medicaments_catalogue' => 42]),
            'http://orders/api/public/stats' => Http::response([
                'commandes_livrees_ou_retirees' => 1305,
                'ordonnances_validees' => 987,
            ]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('MedExpress TOGO en chiffres')
            ->assertSee('+2 300')
            ->assertSee('+1 300')
            ->assertSee('987');
    }
}
