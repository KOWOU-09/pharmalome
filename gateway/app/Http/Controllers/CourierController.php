<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierController extends Controller
{
    public function dashboard()
    {
        try {
            $response = $this->orderService()
                ->get($this->orderServiceUrl('/api/livreur/commandes'));
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger les missions du livreur.', [
                'message' => $exception->getMessage(),
                'livreur_id' => session('pharmalome_user.id'),
            ]);

            return view('livreur.dashboard', ['missions' => [], 'serviceUnavailable' => true]);
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé la liste des missions du livreur.', [
                'status' => $response->status(),
                'livreur_id' => session('pharmalome_user.id'),
            ]);
        }

        $missions = $response->successful() ? $response->json('data', []) : [];

        return view('livreur.dashboard', [
            'missions' => is_array($missions) ? $missions : [],
            'serviceUnavailable' => ! $response->successful(),
        ]);
    }

    public function completeMission(Request $request, int $id)
    {
        try {
            $response = $this->orderService()->patch(
                $this->orderServiceUrl("/api/livreur/commandes/{$id}/statut"),
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service commandes est injoignable pendant la clôture d’une mission.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'La mission n’a pas été clôturée : le service commandes est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé la clôture d’une mission.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'mission' => $response->json('message', 'La mission n’a pas pu être clôturée.'),
            ]);
        }

        return back()->with('status', $response->json('message', 'Livraison terminée.'));
    }

    private function orderService()
    {
        return Http::acceptJson()
            ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
            ->withToken((string) session('pharmalome_token'))
            ->connectTimeout(2)
            ->timeout(6);
    }

    private function orderServiceUrl(string $path): string
    {
        return rtrim((string) config('services.public_stats.orders_url'), '/').$path;
    }
}
