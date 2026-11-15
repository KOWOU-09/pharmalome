<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PharmacienController extends Controller
{
    public function dashboard()
    {
        try {
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->as('orders')->withHeaders($this->internalHeaders())->connectTimeout(1)->timeout(4)
                    ->get($this->url('orders_url', '/api/statistiques')),
                $pool->as('prescriptions')->withHeaders($this->internalHeaders())->connectTimeout(1)->timeout(4)
                    ->get($this->url('orders_url', '/api/ordonnances')),
                $pool->as('alerts')->withHeaders($this->internalHeaders())->connectTimeout(1)->timeout(4)
                    ->get($this->url('catalog_url', '/api/alertes')),
                $pool->as('recent')->withHeaders($this->internalHeaders())->connectTimeout(1)->timeout(4)
                    ->get($this->url('orders_url', '/api/commandes')),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger le tableau de bord pharmacien depuis les microservices.', [
                'message' => $exception->getMessage(),
            ]);

            return view('pharmacien.dashboard', [
                'stats' => [],
                'recentOrders' => [],
                'serviceUnavailable' => true,
            ]);
        }

        $serviceUnavailable = false;
        foreach ($responses as $name => $response) {
            if (! $response instanceof Response || ! $response->successful()) {
                $serviceUnavailable = true;
                Log::warning('Un microservice n’a pas fourni les données du tableau de bord pharmacien.', [
                    'service' => $name,
                    'status' => $response instanceof Response ? $response->status() : null,
                    'error' => $response instanceof \Throwable ? $response->getMessage() : null,
                ]);
            }
        }

        $orderResponse = $responses['orders'] ?? null;
        $prescriptionResponse = $responses['prescriptions'] ?? null;
        $alertResponse = $responses['alerts'] ?? null;
        $recentResponse = $responses['recent'] ?? null;
        $orderStats = $orderResponse instanceof Response && $orderResponse->successful() ? $orderResponse->json('data', []) : [];
        $prescriptions = $prescriptionResponse instanceof Response && $prescriptionResponse->successful() ? $prescriptionResponse->json('data', []) : [];
        $alerts = $alertResponse instanceof Response && $alertResponse->successful() ? $alertResponse->json('data', []) : [];
        $recent = $recentResponse instanceof Response && $recentResponse->successful() ? $recentResponse->json('data.data', []) : [];

        return view('pharmacien.dashboard', [
            'stats' => [
                'commandes' => $orderStats['total_commandes'] ?? null,
                'ordonnances' => is_array($prescriptions) ? count($prescriptions) : null,
                'alertes' => is_array($alerts) ? count($alerts) : null,
                'en_attente' => $orderStats['commandes_en_attente'] ?? null,
            ],
            'recentOrders' => is_array($recent) ? $recent : [],
            'serviceUnavailable' => $serviceUnavailable,
        ]);
    }

    public function ordonnances()
    {
        try {
            $response = $this->request()->get($this->url('orders_url', '/api/ordonnances'));
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger les ordonnances depuis le service commandes.', [
                'message' => $exception->getMessage(),
            ]);

            return view('pharmacien.ordonnances', ['prescriptions' => [], 'serviceUnavailable' => true]);
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé la liste des ordonnances.', ['status' => $response->status()]);
        }

        $prescriptions = $response->successful() ? $response->json('data', []) : [];

        return view('pharmacien.ordonnances', [
            'prescriptions' => is_array($prescriptions) ? $prescriptions : [],
            'serviceUnavailable' => ! $response->successful(),
        ]);
    }

    public function validatePrescription(int $id)
    {
        return $this->updatePrescription($id, 'valider', [
            'pharmacien_id' => (int) session('pharmalome_user.id'),
        ]);
    }

    public function rejectPrescription(Request $request, int $id)
    {
        $validated = $request->validate([
            'motif_refus' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return $this->updatePrescription($id, 'refuser', [
            ...$validated,
            'pharmacien_id' => (int) session('pharmalome_user.id'),
        ]);
    }

    public function prescriptionFile(int $id)
    {
        try {
            $response = $this->request('orders_url')->get($this->url('orders_url', "/api/ordonnances/{$id}/fichier"));
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger le fichier d’ordonnance.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Le fichier est momentanément inaccessible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes n’a pas fourni le fichier d’ordonnance.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->with('warning', 'Le fichier d’ordonnance est introuvable ou inaccessible.');
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'application/octet-stream'),
            'Content-Disposition' => 'inline; filename="ordonnance-'.$id.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function stock()
    {
        try {
            $response = $this->request()->get($this->url('catalog_url', '/api/medicaments'));
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger le stock depuis le service catalogue.', [
                'message' => $exception->getMessage(),
            ]);

            return view('pharmacien.stock', ['items' => [], 'serviceUnavailable' => true]);
        }

        if (! $response->successful()) {
            Log::warning('Le service catalogue a refusé la liste des médicaments.', ['status' => $response->status()]);
        }

        $items = $response->successful() ? $response->json('data.data', []) : [];

        return view('pharmacien.stock', [
            'items' => is_array($items) ? $items : [],
            'serviceUnavailable' => ! $response->successful(),
        ]);
    }

    public function restock(Request $request, int $id)
    {
        $validated = $request->validate([
            'quantite' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        try {
            $response = $this->request()->post(
                $this->url('catalog_url', "/api/medicaments/{$id}/stock/reapprovisionner"),
                $validated,
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service catalogue est injoignable pendant un réapprovisionnement.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Le stock n’a pas été modifié : le service catalogue est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service catalogue a refusé un réapprovisionnement.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'quantite' => $response->json('message', 'Le réapprovisionnement n’a pas pu être effectué.'),
            ]);
        }

        return back()->with('status', $response->json('message', 'Le stock a été réapprovisionné.'));
    }

    private function updatePrescription(int $id, string $action, array $data)
    {
        try {
            $response = $this->request()->patch(
                $this->url('orders_url', "/api/ordonnances/{$id}/{$action}"),
                $data,
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service commandes est injoignable pendant le traitement d’une ordonnance.', [
                'id' => $id,
                'action' => $action,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'L’ordonnance n’a pas été modifiée : le service commandes est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé le traitement d’une ordonnance.', [
                'id' => $id,
                'action' => $action,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'ordonnance' => $response->json('message', 'Le traitement de l’ordonnance a échoué.'),
            ]);
        }

        return back()->with('status', $response->json('message', 'Le statut de l’ordonnance a été mis à jour.'));
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withHeaders($this->internalHeaders())
            ->connectTimeout(2)
            ->timeout(6);
    }

    private function internalHeaders(): array
    {
        return ['X-Internal-Key' => (string) config('services.public_stats.internal_key')];
    }

    private function url(string $service, string $path): string
    {
        return rtrim((string) config('services.public_stats.'.$service), '/').$path;
    }
}
