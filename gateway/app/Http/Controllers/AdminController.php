<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function users()
    {
        try {
            $response = $this->userRequest()->get($this->url('users_url', '/api/users'), request()->only(['search', 'role']));
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger les utilisateurs depuis le service utilisateurs.', [
                'message' => $exception->getMessage(),
            ]);

            return view('admin.users', [
                'users' => [],
                'serviceUnavailable' => true,
                'filters' => request()->only(['search', 'role']),
            ]);
        }

        if (! $response->successful()) {
            Log::warning('Le service utilisateurs a refusé la liste des comptes administrateur.', [
                'status' => $response->status(),
            ]);
        }

        $users = $response->successful() ? $response->json('data', []) : [];

        return view('admin.users', [
            'users' => is_array($users) ? $users : [],
            'serviceUnavailable' => ! $response->successful(),
            'filters' => request()->only(['search', 'role']),
        ]);
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:admin,pharmacien,livreur'],
            'qualification' => ['required_if:role,pharmacien', 'nullable', 'string', 'max:255'],
            'numero_ordre' => ['required_if:role,pharmacien', 'nullable', 'string', 'max:100'],
        ]);

        try {
            $response = $this->userRequest()->post($this->url('users_url', '/api/users'), $validated);
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant une création de compte professionnel.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('warning', 'Le compte n’a pas été créé : le service utilisateurs est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service utilisateurs a refusé la création d’un compte professionnel.', [
                'status' => $response->status(),
            ]);

            return back()->withInput()->withErrors([
                'utilisateur' => $response->json('message', 'La création du compte a échoué.'),
            ]);
        }

        return redirect()->route('admin.utilisateurs')->with('status', $response->json('message'));
    }

    public function resendInvitation(int $id)
    {
        try {
            $response = $this->userRequest()->post($this->url('users_url', "/api/users/{$id}/invitation"));
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant un renvoi d’invitation.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'L’invitation n’a pas été envoyée : le service utilisateurs est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service utilisateurs a refusé un renvoi d’invitation.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'utilisateur' => $response->json('message', 'Le renvoi de l’invitation a échoué.'),
            ]);
        }

        return back()->with('status', $response->json('message'));
    }

    public function updateUser(Request $request, int $id)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:client,pharmacien,livreur,admin'],
            'actif' => ['required', 'boolean'],
            'pharmacien_verifie' => ['nullable', 'boolean'],
        ]);

        try {
            $response = $this->userRequest()->patch(
                $this->url('users_url', "/api/users/{$id}"),
                $validated,
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant une modification de compte.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Le compte n’a pas été modifié : le service utilisateurs est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service utilisateurs a refusé une modification de compte.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'utilisateur' => $response->json('message', 'La modification du compte a échoué.'),
            ]);
        }

        return back()->with('status', 'Le compte a été mis à jour.');
    }

    public function assignCourier(Request $request, int $id)
    {
        $validated = $request->validate([
            'livreur_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validated['livreur_id'] ?? null) {
            try {
                $courier = $this->userRequest()->get(
                    $this->url('users_url', '/api/users/'.$validated['livreur_id']),
                );
            } catch (ConnectionException $exception) {
                Log::error('Le service utilisateurs est injoignable pendant une affectation de livraison.', [
                    'id' => $validated['livreur_id'],
                    'message' => $exception->getMessage(),
                ]);

                return back()->with('warning', 'Le livreur n’a pas pu être vérifié : le service utilisateurs est indisponible.');
            }

            $courierData = $courier->json('data');
            if (! $courier->successful()) {
                Log::warning('Le service utilisateurs n’a pas pu vérifier le compte livreur.', [
                    'id' => $validated['livreur_id'],
                    'status' => $courier->status(),
                ]);

                if ($courier->serverError()) {
                    return back()->with('warning', 'Le livreur n’a pas pu être vérifié : le service utilisateurs est indisponible.');
                }

                return back()->withErrors(['livreur_id' => 'Sélectionnez un compte livreur actif.']);
            }

            if (! is_array($courierData)
                || ($courierData['role'] ?? null) !== 'livreur'
                || ! ($courierData['actif'] ?? false)) {
                return back()->withErrors(['livreur_id' => 'Sélectionnez un compte livreur actif.']);
            }
        }

        try {
            $response = $this->internalRequest()->patch(
                $this->url('orders_url', "/api/commandes/{$id}/livreur"),
                ['livreur_id' => $validated['livreur_id'] ?? null],
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service commandes est injoignable pendant une affectation de livraison.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'La mission n’a pas été affectée : le service commandes est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé une affectation de livreur.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'livreur_id' => $response->json('message', 'L’affectation du livreur a échoué.'),
            ]);
        }

        return back()->with('status', $response->json('message', 'La mission de livraison a été mise à jour.'));
    }

    public function orders()
    {
        try {
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->as('orders')->acceptJson()
                    ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
                    ->connectTimeout(2)->timeout(6)
                    ->get($this->url('orders_url', '/api/commandes')),
                $pool->as('couriers')->acceptJson()
                    ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
                    ->withToken((string) session('pharmalome_token'))
                    ->connectTimeout(2)->timeout(6)
                    ->get($this->url('users_url', '/api/users'), ['role' => 'livreur', 'actif' => 1]),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Impossible de charger les commandes ou les livreurs pour l’administration.', [
                'message' => $exception->getMessage(),
            ]);

            return view('admin.orders', [
                'orders' => [],
                'couriers' => [],
                'serviceUnavailable' => true,
                'couriersUnavailable' => true,
            ]);
        }

        $response = $responses['orders'];
        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé la liste administrateur.', ['status' => $response->status()]);
        }

        $orders = $response->successful() ? $response->json('data.data', []) : [];
        $couriersResponse = $responses['couriers'];

        if (! $couriersResponse->successful()) {
            Log::warning('Le service utilisateurs a refusé la liste des livreurs actifs.', [
                'status' => $couriersResponse->status(),
            ]);
        }

        $couriersUnavailable = ! $couriersResponse->successful();
        $couriers = $couriersResponse->successful() ? $couriersResponse->json('data', []) : [];

        return view('admin.orders', [
            'orders' => is_array($orders) ? $orders : [],
            'serviceUnavailable' => ! $response->successful(),
            'couriers' => is_array($couriers) ? $couriers : [],
            'couriersUnavailable' => $couriersUnavailable,
        ]);
    }

    public function updateOrderStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'statut' => ['required', 'in:en_attente,ordonnance_a_valider,validee,en_preparation,prete,en_livraison,livree,retiree,refusee,annulee'],
        ]);

        try {
            $response = $this->internalRequest()->patch(
                $this->url('orders_url', "/api/commandes/{$id}/statut"),
                $validated,
            );
        } catch (ConnectionException $exception) {
            Log::error('Le service commandes est injoignable pendant une modification de statut.', [
                'id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Le statut n’a pas été modifié : le service commandes est indisponible.');
        }

        if (! $response->successful()) {
            Log::warning('Le service commandes a refusé une modification de statut.', [
                'id' => $id,
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'commande' => $response->json('message', 'La modification de statut a échoué.'),
            ]);
        }

        return back()->with('status', $response->json('message', 'Le statut de la commande a été mis à jour.'));
    }

    private function userRequest(): PendingRequest
    {
        return $this->internalRequest()->withToken((string) session('pharmalome_token'));
    }

    private function internalRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->withHeader('X-Internal-Key', (string) config('services.public_stats.internal_key'))
            ->connectTimeout(2)
            ->timeout(6);
    }

    private function url(string $service, string $path): string
    {
        return rtrim((string) config('services.public_stats.'.$service), '/').$path;
    }
}
