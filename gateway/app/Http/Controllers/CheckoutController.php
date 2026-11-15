<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JsonException;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'json', 'max:8192'],
            'mode_reception' => ['required', 'in:retrait,livraison'],
            'adresse_livraison' => ['required_if:mode_reception,livraison', 'nullable', 'string', 'max:1000'],
            'ordonnance' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        try {
            $cart = json_decode($validated['items'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withInput()->withErrors(['items' => 'Le contenu du panier est invalide. Rechargez votre panier.']);
        }

        if (! is_array($cart) || $cart === []) {
            return back()->withInput()->withErrors(['items' => 'Votre panier est vide.']);
        }

        $cart = Validator::make(['items' => $cart], [
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'items.min' => 'Votre panier est vide.',
            'items.*.id.distinct' => 'Un même produit apparaît plusieurs fois dans le panier.',
        ])->validate()['items'];

        $catalogItems = array_map(
            fn (array $item): array => ['id' => (int) $item['id'], 'quantite' => (int) $item['quantity']],
            $cart,
        );

        try {
            $stockResponse = $this->serviceRequest()
                ->post($this->serviceUrl('catalog_url', '/api/medicaments/verifier'), ['items' => $catalogItems]);
        } catch (ConnectionException $exception) {
            Log::error('Le service catalogue est injoignable pendant une validation de commande.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'items' => 'Le catalogue est momentanément indisponible. Votre panier est conservé, réessayez plus tard.',
            ]);
        }

        if (! $stockResponse->successful()) {
            Log::warning('Le service catalogue a refusé la validation du panier.', [
                'status' => $stockResponse->status(),
            ]);

            return back()->withInput()->withErrors([
                'items' => $stockResponse->json('message', 'Certains produits ne sont plus disponibles. Vérifiez votre panier.'),
            ]);
        }

        $availability = $stockResponse->json('data');
        if (! is_array($availability) || count($availability) !== count($cart)) {
            Log::error('Le service catalogue a renvoyé une réponse de vérification de panier invalide.');

            return back()->withInput()->withErrors(['items' => 'Impossible de vérifier les produits du panier. Réessayez plus tard.']);
        }

        $trustedItems = [];
        $prescriptionRequired = false;
        foreach ($availability as $index => $product) {
            if (! is_array($product) || ! ($product['ok'] ?? false) || ! isset($product['id'], $product['prix'], $product['nom'])) {
                return back()->withInput()->withErrors([
                    'items' => is_array($product) ? ($product['message'] ?? 'Un produit de votre panier n’est plus disponible.') : 'Un produit de votre panier ne peut pas être vérifié.',
                ]);
            }

            if ((int) $product['id'] !== $catalogItems[$index]['id']) {
                Log::error('Les identifiants de produits renvoyés par le catalogue ne correspondent pas au panier.');

                return back()->withInput()->withErrors(['items' => 'La vérification du panier a échoué. Réessayez plus tard.']);
            }

            $prescriptionRequired = $prescriptionRequired || (bool) ($product['ordonnance_requise'] ?? false);
            $trustedItems[] = [
                'id' => (int) $product['id'],
                'nom' => (string) $product['nom'],
                'prix' => (int) $product['prix'],
                'quantite' => $catalogItems[$index]['quantite'],
            ];
        }

        if ($prescriptionRequired && ! $request->hasFile('ordonnance')) {
            return back()->withInput()->withErrors([
                'ordonnance' => 'Une ordonnance valide est nécessaire pour les produits de ce panier.',
            ]);
        }

        $orderPayload = [
            'user_id' => (int) session('pharmalome_user.id'),
            'mode_reception' => $validated['mode_reception'],
            'adresse_livraison' => $validated['adresse_livraison'] ?? '',
            'items' => $trustedItems,
        ];

        try {
            $orderRequest = $this->serviceRequest();
            if ($request->hasFile('ordonnance')) {
                $file = $request->file('ordonnance');
                $orderRequest->asMultipart()->attach(
                    'ordonnance',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                );
                $orderPayload['items'] = json_encode($trustedItems, JSON_THROW_ON_ERROR);
            }

            $orderResponse = $orderRequest->post(
                $this->serviceUrl('orders_url', '/api/commandes'),
                $orderPayload,
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service commandes est injoignable pendant la création d’une commande.', [
                'message' => $exception->getMessage(),
                'user_id' => $orderPayload['user_id'],
            ]);

            return back()->withInput()->withErrors([
                'items' => 'Le service de commande est momentanément indisponible. Votre panier est conservé.',
            ]);
        } catch (JsonException $exception) {
            Log::error('Impossible de préparer les articles pour le service commandes.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['items' => 'Impossible de préparer votre commande. Réessayez plus tard.']);
        }

        if (! $orderResponse->successful() || ! is_array($orderResponse->json('data'))) {
            Log::warning('Le service commandes a refusé ou mal traité une création de commande.', [
                'status' => $orderResponse->status(),
                'user_id' => $orderPayload['user_id'],
            ]);

            return back()->withInput()->withErrors([
                'items' => $orderResponse->json('message', 'La commande n’a pas pu être enregistrée. Réessayez plus tard.'),
            ]);
        }

        $orderId = $orderResponse->json('data.id');

        return redirect()->route('commandes')->with([
            'status' => $orderId
                ? 'Votre commande #'.$orderId.' a bien été enregistrée.'
                : 'Votre commande a bien été enregistrée.',
            'clear_cart' => true,
        ]);
    }

    private function serviceRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
            ->connectTimeout(2)
            ->timeout(8);
    }

    private function serviceUrl(string $service, string $path): string
    {
        return rtrim((string) config('services.public_stats.'.$service), '/').$path;
    }
}
