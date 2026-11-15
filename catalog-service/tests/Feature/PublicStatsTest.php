<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_the_number_of_catalogue_medicines_with_the_internal_key(): void
    {
        $category = Category::create(['libelle' => 'Douleurs']);

        Medicament::create([
            'categorie_id' => $category->id,
            'nom' => 'Paracétamol',
            'prix' => 2500,
            'quantite_stock' => 12,
        ]);
        Medicament::create([
            'categorie_id' => $category->id,
            'nom' => 'Ibuprofène',
            'prix' => 3000,
            'quantite_stock' => 8,
        ]);

        $this->getJson('/api/public/stats')
            ->assertForbidden();

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/public/stats')
            ->assertOk()
            ->assertExactJson(['medicaments_catalogue' => 2]);
    }
}
