<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogStorefrontService
{
    /**
     * @return array{categories: array, products: array, pagination: array, unavailable: bool}
     */
    public function snapshot(array $filters = []): array
    {
        $query = array_filter([
            'search' => $filters['search'] ?? null,
            'categorie' => $filters['categorie'] ?? null,
            'ordonnance' => $filters['ordonnance'] ?? null,
            'promotion' => $filters['promotion'] ?? null,
            'prix_max' => $filters['prix_max'] ?? null,
            'page' => $filters['page'] ?? 1,
            'per_page' => 100,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->as('categories')->withHeaders($this->headers())
                    ->connectTimeout(1)->timeout(3)
                    ->get($this->url('/api/categories')),
                $pool->as('products')->withHeaders($this->headers())
                    ->connectTimeout(1)->timeout(3)
                    ->get($this->url('/api/medicaments'), $query),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Le catalogue est momentanément inaccessible pour la boutique.', [
                'message' => $exception->getMessage(),
            ]);

            return ['categories' => [], 'products' => [], 'pagination' => [], 'unavailable' => true];
        }

        $categoriesResponse = $responses['categories'] ?? null;
        $productsResponse = $responses['products'] ?? null;
        $categories = $this->responseData($categoriesResponse, 'categories');
        $productPage = $this->responseData($productsResponse, 'products');

        return [
            'categories' => is_array($categories) ? array_map($this->normalizeCategory(...), $categories) : [],
            'products' => is_array($productPage['data'] ?? null)
                ? array_map($this->normalizeProduct(...), $productPage['data'])
                : [],
            'pagination' => is_array($productPage) ? $productPage : [],
            'unavailable' => ! ($categoriesResponse instanceof Response && $categoriesResponse->successful())
                || ! ($productsResponse instanceof Response && $productsResponse->successful())
                || ! is_array($categories)
                || ! is_array($productPage)
                || ! is_array($productPage['data'] ?? null),
        ];
    }

    /**
     * @return array{product: array|null, similar: array, unavailable: bool}
     */
    public function product(int $id): array
    {
        try {
            $response = $this->request()->get($this->url("/api/medicaments/{$id}"));
        } catch (ConnectionException $exception) {
            Log::warning('Le catalogue est momentanément inaccessible pour la fiche produit.', [
                'product_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return ['product' => null, 'similar' => [], 'unavailable' => true];
        }

        if ($response->status() === 404) {
            return ['product' => null, 'similar' => [], 'unavailable' => false];
        }

        if (! $response->successful() || ! is_array($response->json('data'))) {
            Log::warning('Le service catalogue a refusé la fiche produit.', [
                'product_id' => $id,
                'status' => $response->status(),
            ]);

            return ['product' => null, 'similar' => [], 'unavailable' => true];
        }

        $product = $this->normalizeProduct($response->json('data'));

        try {
            $similarResponse = $this->request()->get($this->url('/api/medicaments'), [
                'categorie_id' => $response->json('data.categorie_id'),
                'per_page' => 4,
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Les produits similaires ne sont pas disponibles.', [
                'product_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return ['product' => $product, 'similar' => [], 'unavailable' => false];
        }

        if (! $similarResponse->successful()) {
            Log::warning('Le service catalogue a refusé les produits similaires.', [
                'product_id' => $id,
                'status' => $similarResponse->status(),
            ]);
        }

        $similar = $similarResponse->successful() ? $similarResponse->json('data.data', []) : [];

        return [
            'product' => $product,
            'similar' => is_array($similar)
                ? array_map($this->normalizeProduct(...), array_filter($similar, fn (array $item) => (int) $item['id'] !== $id))
                : [],
            'unavailable' => false,
        ];
    }

    private function responseData(mixed $response, string $service): ?array
    {
        if (! $response instanceof Response || ! $response->successful()) {
            Log::warning('Le service catalogue n’a pas fourni les données de la boutique.', [
                'resource' => $service,
                'status' => $response instanceof Response ? $response->status() : null,
                'error' => $response instanceof \Throwable ? $response->getMessage() : null,
            ]);

            return null;
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            Log::warning('Le service catalogue a renvoyé un format de données invalide pour la boutique.', [
                'resource' => $service,
            ]);

            return null;
        }

        return $data;
    }

    private function normalizeCategory(array $category): array
    {
        return [
            'id' => (int) $category['id'],
            'nom' => (string) $category['libelle'],
            'icone' => 'medical_services',
        ];
    }

    private function normalizeProduct(array $product): array
    {
        $promotion = (bool) ($product['promotion'] ?? false)
            && isset($product['prix_promotion'])
            && (int) $product['prix_promotion'] > 0
            && (int) $product['prix_promotion'] < (int) $product['prix'];
        $stock = (int) ($product['quantite_stock'] ?? 0);

        return [
            'id' => (int) $product['id'],
            'nom' => (string) $product['nom'],
            'categorie' => (string) ($product['categorie']['libelle'] ?? ''),
            'prix' => $promotion ? (int) $product['prix_promotion'] : (int) $product['prix'],
            'prix_initial' => (int) $product['prix'],
            'stock' => $stock,
            'ordonnance' => (bool) ($product['ordonnance_requise'] ?? false),
            'description' => (string) ($product['description'] ?? ''),
            'dosage' => (string) ($product['dosage'] ?? ''),
            'laboratoire' => (string) ($product['laboratoire'] ?? ''),
            'badge' => $promotion ? 'Promotion' : (($product['ordonnance_requise'] ?? false) ? 'Sur ordonnance' : ($stock > 0 ? 'En stock' : 'Indisponible')),
            'promotion' => $promotion,
        ];
    }

    private function request()
    {
        return Http::acceptJson()
            ->withHeaders($this->headers())
            ->connectTimeout(1)
            ->timeout(3);
    }

    private function headers(): array
    {
        return ['X-Internal-Key' => (string) config('services.public_stats.internal_key')];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.public_stats.catalog_url'), '/').$path;
    }
}
