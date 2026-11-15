<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        return $this->authenticate($request, '/api/register', $validated, true);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'numero_ordre' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $response = $this->userService()
                ->withToken((string) $request->session()->get('pharmalome_token'))
                ->patch($this->userServiceUrl('/api/me'), $validated);
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant une modification de profil.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'profil' => 'Le service de compte est momentanément indisponible.',
            ]);
        }

        if (! $response->successful() || ! is_array($response->json('user'))) {
            Log::warning('Le service utilisateurs a refusé la modification du profil.', [
                'status' => $response->status(),
            ]);

            return back()->withInput()->withErrors([
                'profil' => $response->json('message', 'La mise à jour du profil a échoué.'),
            ]);
        }

        $request->session()->put('pharmalome_user', $this->sessionUser($response->json('user')));

        return back()->with('status', 'Vos informations ont été mises à jour.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        try {
            $response = $this->userService()
                ->withToken((string) $request->session()->get('pharmalome_token'))
                ->patch($this->userServiceUrl('/api/me/password'), $validated);
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant une modification de mot de passe.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'mot_de_passe' => 'Le service de compte est momentanément indisponible.',
            ]);
        }

        $token = $response->json('token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            Log::warning('Le service utilisateurs a refusé la modification du mot de passe.', [
                'status' => $response->status(),
            ]);

            return back()->withErrors([
                'mot_de_passe' => $response->json('message', 'La modification du mot de passe a échoué.'),
            ]);
        }

        $request->session()->put('pharmalome_token', $token);

        return back()->with('status', 'Votre mot de passe a été modifié.');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        return $this->forwardPasswordRequest(
            $request,
            '/api/password/email',
            $validated,
            'Si cette adresse est associée à un compte, un lien de réinitialisation lui a été envoyé.',
        );
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return $this->forwardPasswordRequest(
            $request,
            '/api/password/reset',
            $validated,
            'Mot de passe réinitialisé. Vous pouvez vous connecter.',
            route('login'),
        );
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        return $this->authenticate($request, '/api/login', $validated, false);
    }

    public function logout(Request $request)
    {
        $token = $request->session()->get('pharmalome_token');
        $serviceUnavailable = false;

        if (is_string($token) && $token !== '') {
            try {
                $response = $this->userService()
                    ->withToken($token)
                    ->post($this->userServiceUrl('/api/logout'));

                if (! $response->successful()) {
                    Log::warning('Le service utilisateurs n’a pas révoqué le jeton à la déconnexion.', [
                        'status' => $response->status(),
                    ]);
                    $serviceUnavailable = true;
                }
            } catch (ConnectionException $exception) {
                Log::warning('Impossible de joindre le service utilisateurs à la déconnexion.', [
                    'message' => $exception->getMessage(),
                ]);
                $serviceUnavailable = true;
            }
        }

        $request->session()->forget(['pharmalome_user', 'pharmalome_token']);
        $request->session()->regenerate();

        return redirect()->route('home')->with(
            'status',
            $serviceUnavailable
                ? 'Votre session locale est fermée, mais le service n’a pas confirmé la révocation du jeton.'
                : 'Vous êtes déconnecté(e).',
        );
    }

    private function authenticate(Request $request, string $path, array $credentials, bool $isRegistration)
    {
        try {
            $response = $this->userService()->post($this->userServiceUrl($path), $credentials);
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pendant une tentative d’authentification.', [
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'Le service de compte est momentanément indisponible. Réessayez plus tard.']);
        }

        if (! $response->successful()) {
            $errors = $response->json('errors');

            if ($response->status() === 422 && is_array($errors)) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->withErrors($errors);
            }

            if ($response->status() === 401) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->withErrors(['email' => 'Adresse e-mail ou mot de passe incorrect.']);
            }

            Log::warning('Le service utilisateurs a refusé la demande d’authentification.', [
                'status' => $response->status(),
                'registration' => $isRegistration,
            ]);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'Impossible de traiter la demande pour le moment. Réessayez plus tard.']);
        }

        $token = $response->json('token');
        $user = $response->json('user');

        if (! is_string($token) || $token === '' || ! is_array($user) || ! isset($user['id'], $user['name'])) {
            Log::error('Le service utilisateurs a renvoyé une réponse d’authentification invalide.');

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'Réponse de connexion invalide. Contactez le support.']);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'pharmalome_token' => $token,
            'pharmalome_user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'] ?? $credentials['email'],
                'role' => $user['role'] ?? 'client',
                'telephone' => $user['telephone'] ?? null,
                'nom' => $user['nom'] ?? null,
                'prenom' => $user['prenom'] ?? null,
                'adresse' => $user['adresse'] ?? null,
                'qualification' => $user['qualification'] ?? null,
                'numero_ordre' => $user['numero_ordre'] ?? null,
                'pharmacien_verifie' => (bool) ($user['pharmacien_verifie'] ?? false),
            ],
        ]);

        return redirect()->intended(route($isRegistration ? 'compte' : 'home'))->with(
            'status',
            $isRegistration ? 'Votre compte a été créé. Bienvenue sur MedExpress TOGO !' : 'Connexion réussie. Bienvenue !',
        );
    }

    private function userService()
    {
        return Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(5);
    }

    private function userServiceUrl(string $path): string
    {
        return rtrim((string) config('services.public_stats.users_url'), '/').$path;
    }

    private function forwardPasswordRequest(
        Request $request,
        string $path,
        array $payload,
        string $successMessage,
        ?string $successRoute = null,
    ) {
        try {
            $response = $this->userService()->post($this->userServiceUrl($path), $payload);
        } catch (ConnectionException $exception) {
            Log::error('Le service utilisateurs est injoignable pour une opération de mot de passe.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput($request->except(['password', 'password_confirmation', 'token']))
                ->withErrors(['email' => 'Le service de compte est momentanément indisponible.']);
        }

        if (! $response->successful()) {
            Log::warning('Le service utilisateurs a refusé une opération de mot de passe.', [
                'status' => $response->status(),
            ]);

            return back()->withInput($request->except(['password', 'password_confirmation', 'token']))
                ->withErrors([
                    'email' => $response->json('message', 'Impossible de traiter la demande.'),
                ]);
        }

        return $successRoute
            ? redirect($successRoute)->with('status', $successMessage)
            : back()->with('status', $successMessage);
    }

    private function sessionUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'client',
            'telephone' => $user['telephone'] ?? null,
            'nom' => $user['nom'] ?? null,
            'prenom' => $user['prenom'] ?? null,
            'adresse' => $user['adresse'] ?? null,
            'qualification' => $user['qualification'] ?? null,
            'numero_ordre' => $user['numero_ordre'] ?? null,
            'pharmacien_verifie' => (bool) ($user['pharmacien_verifie'] ?? false),
        ];
    }
}
