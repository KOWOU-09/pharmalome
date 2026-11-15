<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogueAdminController extends Controller
{
    public function index()
    {
        $page = max(request()->integer('page', 1), 1);

        try {
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->as('categories')->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
                    ->connectTimeout(2)->timeout(6)
                    ->get($this->url('/api/categories')),
                $pool->as('products')->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
                    ->connectTimeout(2)->timeout(6)
                    ->get($this->url('/api/medicaments'), ['page' => $page, 'per_page' => 20]),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger le catalogue pour l’administration.', [
                'message' => $exception->getMessage(),
            ]);

            return view('admin.catalogue', [
                'categories' => [],
                'products' => [],
                'pagination' => ['current_page' => $page, 'last_page' => 1],
                'serviceUnavailable' => true,
            ]);
        }

        $categoriesResponse = $responses['categories'];
        $productsResponse = $responses['products'];
        $serviceUnavailable = ! $categoriesResponse->successful() || ! $productsResponse->successful();
        if ($serviceUnavailable) {
            Log::warning('Le service catalogue a refusé les données de gestion.', [
                'categories_status' => $categoriesResponse->status(),
                'products_status' => $productsResponse->status(),
            ]);
        }

        $productPage = $productsResponse->successful() ? $productsResponse->json('data', []) : [];

        return view('admin.catalogue', [
            'categories' => $categoriesResponse->successful() ? $categoriesResponse->json('data', []) : [],
            'products' => is_array($productPage['data'] ?? null) ? $productPage['data'] : [],
            'pagination' => [
                'current_page' => max((int) ($productPage['current_page'] ?? $page), 1),
                'last_page' => max((int) ($productPage['last_page'] ?? 1), 1),
            ],
            'serviceUnavailable' => $serviceUnavailable,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate(['libelle' => ['required', 'string', 'max:255']]);

        return $this->sendMutation('post', '/api/categories', $validated, 'La catégorie a été ajoutée.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $validated = $request->validate(['libelle' => ['required', 'string', 'max:255']]);

        return $this->sendMutation('put', "/api/categories/{$id}", $validated, 'La catégorie a été mise à jour.');
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'categorie_id' => ['required', 'integer', 'min:1'],
            'nom' => ['required', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'laboratoire' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'prix' => ['required', 'integer', 'min:1'],
            'promotion' => ['nullable', 'boolean'],
            'prix_promotion' => ['nullable', 'required_if:promotion,1', 'integer', 'min:1', 'lt:prix'],
            'quantite_stock' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'date_peremption' => ['nullable', 'date'],
            'ordonnance_requise' => ['nullable', 'boolean'],
        ]);
        $validated['ordonnance_requise'] = $request->boolean('ordonnance_requise');
        $validated['promotion'] = $request->boolean('promotion');
        if (! $validated['promotion']) {
            $validated['prix_promotion'] = null;
        }

        return $this->sendMutation('post', '/api/medicaments', $validated, 'Le produit a été ajouté au catalogue.');
    }

    public function updateProduct(Request $request, int $id)
    {
        $validated = $request->validate([
            'categorie_id' => ['required', 'integer', 'min:1'],
            'nom' => ['required', 'string', 'max:255'],
            'prix' => ['required', 'integer', 'min:1'],
            'promotion' => ['nullable', 'boolean'],
            'prix_promotion' => ['nullable', 'required_if:promotion,1', 'integer', 'min:1', 'lt:prix'],
            'quantite_stock' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'laboratoire' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date_peremption' => ['nullable', 'date'],
            'ordonnance_requise' => ['nullable', 'boolean'],
        ]);
        $validated['ordonnance_requise'] = $request->boolean('ordonnance_requise');
        $validated['promotion'] = $request->boolean('promotion');
        if (! $validated['promotion']) {
            $validated['prix_promotion'] = null;
        }

        return $this->sendMutation('put', "/api/medicaments/{$id}", $validated, 'Le produit a été mis à jour.');
    }

    public function destroyProduct(int $id)
    {
        return $this->sendMutation('delete', "/api/medicaments/{$id}", [], 'Le produit a été retiré du catalogue.');
    }

    private function sendMutation(string $method, string $path, array $data, string $successMessage)
    {
        try {
            $response = $this->request()->$method($this->url($path), $data);
        } catch (ConnectionException $exception) {
            Log::error('Le service catalogue est injoignable pendant une modification administrateur.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Le catalogue n’a pas été modifié : le service catalogue est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service catalogue a refusé une modification administrateur.', [
                'path' => $path,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'catalogue' => $response->json('message', 'La modification du catalogue a échoué.'),
            ])->withInput();
        }

        return back()->with('status', $response->json('message', $successMessage));
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
            ->connectTimeout(2)
            ->timeout(6);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.public_stats.catalog_url'), '/').$path;
    }
}
