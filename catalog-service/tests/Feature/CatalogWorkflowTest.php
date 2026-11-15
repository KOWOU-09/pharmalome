<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_filters_categories_promotions_and_limits_page_size(): void
    {
        $category = Category::create(['libelle' => 'Soins']);
        Medicament::create([
            'categorie_id' => $category->id,
            'nom' => 'Produit en promotion',
            'prix' => 3000,
            'prix_promotion' => 2500,
            'promotion' => true,
        ]);
        Medicament::create([
            'categorie_id' => $category->id,
            'nom' => 'Produit standard',
            'prix' => 2000,
        ]);

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.medicaments_count', 2);

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/medicaments?categorie=Soins&promotion=1&per_page=500')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.per_page', 100)
            ->assertJsonPath('data.data.0.nom', 'Produit en promotion')
            ->assertJsonPath('data.data.0.prix_promotion', 2500)
            ->assertJsonPath('data.data.0.categorie.libelle', 'Soins');
    }

    public function test_cart_verification_uses_the_promotional_price_for_orders(): void
    {
        $category = Category::create(['libelle' => 'Soins']);
        $product = Medicament::create([
            'categorie_id' => $category->id,
            'nom' => 'Produit en promotion',
            'prix' => 3000,
            'prix_promotion' => 2500,
            'promotion' => true,
            'quantite_stock' => 5,
        ]);

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->postJson('/api/medicaments/verifier', [
                'items' => [['id' => $product->id, 'quantite' => 1]],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.prix', 2500)
            ->assertJsonPath('data.0.ok', true);
    }

    public function test_promotional_price_must_be_less_than_the_regular_price(): void
    {
        $category = Category::create(['libelle' => 'Soins']);

        $this->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->postJson('/api/medicaments', [
                'categorie_id' => $category->id,
                'nom' => 'Produit invalide',
                'prix' => 2000,
                'prix_promotion' => 2500,
                'promotion' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('prix_promotion');
    }
}
