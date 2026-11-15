<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureGatewayAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->session()->get('pharmalome_user');
        if (! is_array($user)) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login')->with('status', 'Connectez-vous pour accéder à cette page.');
        }

        $token = $request->session()->get('pharmalome_token');
        if (! is_string($token) || $token === '') {
            return $this->expireSession($request);
        }

        try {
            $identity = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->get(rtrim((string) config('services.public_stats.users_url'), '/').'/api/me');
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant une vérification de session.', [
                'message' => $exception->getMessage(),
            ]);

            abort(503, 'Le service de compte est temporairement indisponible.');
        }

        if (in_array($identity->status(), [401, 403], true)) {
            return $this->expireSession($request);
        }

        if (! $identity->successful()) {
            Log::warning('Le service utilisateurs a refusé la vérification d’une session.', [
                'status' => $identity->status(),
            ]);

            abort(503, 'Impossible de vérifier le compte.');
        }

        $currentUser = $identity->json();
        if (! is_array($currentUser)
            || ! isset($currentUser['id'], $currentUser['name'], $currentUser['role'])
            || ! ($currentUser['actif'] ?? false)) {
            Log::error('Le service utilisateurs a renvoyé un profil incomplet pendant la vérification de session.');

            abort(503, 'Impossible de vérifier le compte.');
        }

        $request->session()->put('pharmalome_user', [
            'id' => $currentUser['id'],
            'name' => $currentUser['name'],
            'email' => $currentUser['email'] ?? '',
            'role' => $currentUser['role'],
            'telephone' => $currentUser['telephone'] ?? null,
            'nom' => $currentUser['nom'] ?? null,
            'prenom' => $currentUser['prenom'] ?? null,
            'adresse' => $currentUser['adresse'] ?? null,
            'qualification' => $currentUser['qualification'] ?? null,
            'numero_ordre' => $currentUser['numero_ordre'] ?? null,
            'pharmacien_verifie' => (bool) ($currentUser['pharmacien_verifie'] ?? false),
        ]);

        return $next($request);
    }

    private function expireSession(Request $request): Response
    {
        $request->session()->forget(['pharmalome_user', 'pharmalome_token']);
        $request->session()->regenerate();

        return redirect()->route('login')->with('status', 'Votre session a expiré. Connectez-vous de nouveau.');
    }
}
