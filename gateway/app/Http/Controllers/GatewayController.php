<?php

namespace App\Http\Controllers;

use App\Services\CatalogStorefrontService;
use App\Services\PublicStatsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    public function home(PublicStatsService $publicStats, CatalogStorefrontService $catalog)
    {
        $stats = array_map(
            fn (?int $value): array => [
                'value' => $value,
                'display' => $this->formatStat($value),
            ],
            $publicStats->get(),
        );

        $catalogue = $catalog->snapshot();
        $products = $catalogue['products'];
        usort($products, fn (array $a, array $b) => (int) $b['promotion'] <=> (int) $a['promotion']);

        return view('home', [
            'categories' => $catalogue['categories'],
            'products' => array_slice($products, 0, 4),
            'promotionProducts' => array_values(array_filter($products, fn (array $product) => $product['promotion'])),
            'catalogUnavailable' => $catalogue['unavailable'],
            'stats' => $stats,
        ]);
    }

    private function formatStat(?int $value): string
    {
        if ($value === null) {
            return 'Indisponible';
        }

        if ($value < 1000) {
            return number_format($value, 0, ',', ' ');
        }

        return '+'.number_format(intdiv($value, 100) * 100, 0, ',', ' ');
    }

    public function catalog(Request $request, CatalogStorefrontService $catalog)
    {
        $catalogue = $catalog->snapshot($request->only(['search', 'categorie', 'ordonnance', 'promotion', 'prix_max', 'page']));

        return view('catalogue.index', [
            'products' => $catalogue['products'],
            'categories' => $catalogue['categories'],
            'pagination' => $catalogue['pagination'],
            'serviceUnavailable' => $catalogue['unavailable'],
        ]);
    }

    public function showProduct(int $id, CatalogStorefrontService $catalog)
    {
        $result = $catalog->product($id);
        abort_if($result['unavailable'], 503);
        abort_if($result['product'] === null, 404);

        return view('catalogue.show', [
            'product' => $result['product'],
            'similar' => array_slice($result['similar'], 0, 3),
        ]);
    }

    public function cart()
    {
        return view('panier.index', ['items' => []]);
    }

    public function checkout()
    {
        return view('commandes.checkout');
    }

    public function orders()
    {
        $orders = [];
        $ordersUnavailable = false;

        try {
            $response = Http::acceptJson()
                ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
                ->connectTimeout(2)
                ->timeout(5)
                ->get(rtrim((string) config('services.public_stats.orders_url'), '/').'/api/commandes', [
                    'user_id' => session('pharmalome_user.id'),
                ]);

            if ($response->successful()) {
                $payload = $response->json('data.data');
                $orders = is_array($payload) ? $payload : [];
            } else {
                $ordersUnavailable = true;
                Log::warning('Le service commandes n’a pas pu fournir l’historique du client.', [
                    'status' => $response->status(),
                    'user_id' => session('pharmalome_user.id'),
                ]);
            }
        } catch (ConnectionException $exception) {
            $ordersUnavailable = true;
            Log::warning('Impossible de joindre le service commandes pour l’historique du client.', [
                'message' => $exception->getMessage(),
                'user_id' => session('pharmalome_user.id'),
            ]);
        }

        return view('commandes.index', compact('orders', 'ordersUnavailable'));
    }

    public function account()
    {
        return view('compte.index', ['user' => session('pharmalome_user')]);
    }

    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function resetPassword(Request $request)
    {
        return view('auth.reset-password', [
            'token' => $request->query('token', ''),
            'email' => $request->query('email', ''),
        ]);
    }

    public function adminDashboard(PublicStatsService $publicStats)
    {
        return view('admin.dashboard', ['stats' => $publicStats->get()]);
    }
}
