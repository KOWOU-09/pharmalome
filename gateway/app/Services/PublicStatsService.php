<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublicStatsService
{
    private const RECENT_CACHE_KEY = 'pharmalome.public-stats.recent';

    private const LAST_KNOWN_CACHE_KEY = 'pharmalome.public-stats.last-known';

    private const EMPTY_STATS = [
        'utilisateurs_inscrits' => null,
        'medicaments_catalogue' => null,
        'commandes_livrees_ou_retirees' => null,
        'ordonnances_validees' => null,
    ];

    /**
     * @return array<string, int|null>
     */
    public function get(): array
    {
        $cached = Cache::get(self::RECENT_CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $lock = Cache::lock(self::RECENT_CACHE_KEY . '.lock', 5);

        if (! $lock->get()) {
            return Cache::get(self::LAST_KNOWN_CACHE_KEY, self::EMPTY_STATS);
        }

        try {
            $cached = Cache::get(self::RECENT_CACHE_KEY);

            if ($cached !== null) {
                return $cached;
            }

            $stats = $this->refresh();
            Cache::put(self::RECENT_CACHE_KEY, $stats, now()->addSeconds(30));

            return $stats;
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, int|null>
     */
    private function refresh(): array
    {
        $stats = Cache::get(self::LAST_KNOWN_CACHE_KEY, self::EMPTY_STATS);
        $key = (string) config('services.public_stats.internal_key');

        try {
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->as('users')->withHeaders(['X-Internal-Key' => $key])
                    ->connectTimeout(1)
                    ->timeout(3)
                    ->get(rtrim(config('services.public_stats.users_url'), '/') . '/api/public/stats'),
                $pool->as('catalog')->withHeaders(['X-Internal-Key' => $key])
                    ->connectTimeout(1)
                    ->timeout(3)
                    ->get(rtrim(config('services.public_stats.catalog_url'), '/') . '/api/public/stats'),
                $pool->as('orders')->withHeaders(['X-Internal-Key' => $key])
                    ->connectTimeout(1)
                    ->timeout(3)
                    ->get(rtrim(config('services.public_stats.orders_url'), '/') . '/api/public/stats'),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de joindre un service pour actualiser les statistiques publiques.', [
                'message' => $exception->getMessage(),
            ]);

            return $stats;
        }

        $services = [
            'users' => ['utilisateurs_inscrits'],
            'catalog' => ['medicaments_catalogue'],
            'orders' => ['commandes_livrees_ou_retirees', 'ordonnances_validees'],
        ];
        $updated = false;

        foreach ($services as $service => $countKeys) {
            $response = $responses[$service] ?? null;

            if (! $response instanceof Response || ! $response->successful()) {
                Log::warning('Un service n’a pas fourni ses statistiques publiques.', [
                    'service' => $service,
                    'status' => $response instanceof Response ? $response->status() : null,
                    'error' => $response instanceof \Throwable ? $response->getMessage() : null,
                ]);

                continue;
            }

            foreach ($countKeys as $countKey) {
                $count = $response->json($countKey);
                $integerCount = filter_var($count, FILTER_VALIDATE_INT);

                if ($integerCount === false || $integerCount < 0) {
                    Log::warning('Un service a renvoyé un compteur public invalide.', [
                        'service' => $service,
                        'compteur' => $countKey,
                    ]);

                    continue;
                }

                $stats[$countKey] = $integerCount;
                $updated = true;
            }
        }

        if ($updated) {
            Cache::put(self::LAST_KNOWN_CACHE_KEY, $stats, now()->addDays(30));
        }

        return $stats;
    }
}
