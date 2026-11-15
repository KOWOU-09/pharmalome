<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_the_number_of_active_clients_and_requires_the_internal_key(): void
    {
        User::factory()->create(['role' => 'client', 'actif' => true]);
        User::factory()->create(['role' => 'client', 'actif' => false]);
        User::factory()->create(['role' => 'pharmacien', 'actif' => true]);

        $this->getJson('/api/public/stats')
            ->assertForbidden();

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/public/stats')
            ->assertOk()
            ->assertExactJson(['utilisateurs_inscrits' => 1]);
    }
}
