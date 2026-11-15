<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'required_without_all:nom,prenom', 'string', 'max:255'],
            'nom' => ['required_without:name', 'string', 'max:120'],
            'prenom' => ['required_without:name', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['name'] = $this->displayName($validated);
        unset($validated['password_confirmation']);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'role' => 'client',
            'actif' => true,
        ]);

        $token = $user->createToken('pharmalome')->plainTextToken;

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->actif) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $user->createToken('pharmalome')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->tokens()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user() ?? ['message' => 'Utilisateur non authentifié.']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'numero_ordre' => ['nullable', 'string', 'max:100'],
        ]);

        $user->fill([
            ...$validated,
            'name' => $this->displayName($validated),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->role === 'pharmacien' && (
            $user->isDirty('nom')
            || $user->isDirty('prenom')
            || $user->isDirty('qualification')
            || $user->isDirty('numero_ordre')
        )) {
            $user->pharmacien_verifie = false;
        }

        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
                'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);
        $user->tokens()->delete();
        $token = $user->createToken('pharmalome')->plainTextToken;

        return response()->json([
            'message' => 'Mot de passe modifié.',
            'token' => $token,
        ]);
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $exception) {
            Log::error('Échec de l’envoi du lien de réinitialisation.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Le service de messagerie est temporairement indisponible.',
            ], 503);
        }

        return response()->json([
            'message' => 'Si cette adresse correspond à un compte, un lien de réinitialisation lui a été envoyé.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Le lien de réinitialisation est invalide ou expiré.',
            ], 422);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }

    public function publicStats()
    {
        return response()->json([
            'utilisateurs_inscrits' => User::query()
                ->where('role', 'client')
                ->where('actif', true)
                ->count(),
        ]);
    }

    public function index()
    {
        $this->ensureAdmin(request());

        $query = User::query()->latest();
        $search = request()->string('search')->trim()->value();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $pattern = '%'.mb_strtolower($search).'%';
                $builder->whereRaw('LOWER(name) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(telephone) LIKE ?', [$pattern]);
            });
        }

        if (request()->filled('role')) {
            $query->where('role', request('role'));
        }

        if (request()->filled('actif')) {
            $query->where('actif', request()->boolean('actif'));
        }

        return response()->json([
            'data' => $query->get(),
            'message' => 'Liste des utilisateurs.',
        ]);
    }

    public function show(User $user)
    {
        $this->ensureAdmin(request());

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'actif' => ['nullable', 'boolean'],
            'role' => ['nullable', 'in:client,pharmacien,livreur,admin'],
            'pharmacien_verifie' => ['nullable', 'boolean'],
        ]);

        if ($request->user()->is($user) && (
            array_key_exists('role', $validated) && $validated['role'] !== 'admin'
            || array_key_exists('actif', $validated) && ! $validated['actif']
        )) {
            return response()->json([
                'message' => 'Vous ne pouvez pas désactiver ou rétrograder votre propre compte.',
            ], 422);
        }

        if (($validated['pharmacien_verifie'] ?? false) && (
            $user->role !== 'pharmacien'
            || blank($user->qualification)
            || blank($user->numero_ordre)
        )) {
            return response()->json([
                'message' => 'La qualification et le numéro d’ordre doivent être renseignés avant la vérification.',
            ], 422);
        }

        if (isset($validated['role']) && $validated['role'] !== 'pharmacien') {
            $validated['pharmacien_verifie'] = false;
        }

        $user->fill($validated);
        $credentialsChanged = $user->isDirty(['role', 'actif', 'pharmacien_verifie']);
        $user->save();

        if ($credentialsChanged) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(User $user)
    {
        $this->ensureAdmin(request());

        if (request()->user()->is($user)) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    public function storeStaff(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'prenom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'telephone' => ['required', 'string', 'max:20'],
            'role' => ['required', Rule::in(['admin', 'pharmacien', 'livreur'])],
            'qualification' => ['required_if:role,pharmacien', 'nullable', 'string', 'max:255'],
            'numero_ordre' => ['required_if:role,pharmacien', 'nullable', 'string', 'max:100'],
        ]);

        try {
            $user = DB::transaction(function () use ($validated): User {
                $user = User::create([
                    ...$validated,
                    'name' => $this->displayName($validated),
                    'password' => Hash::make(Str::random(64)),
                    'actif' => true,
                    'pharmacien_verifie' => false,
                ]);

                $status = Password::sendResetLink(['email' => $user->email]);

                if ($status !== Password::RESET_LINK_SENT) {
                    throw new \RuntimeException('Impossible d’envoyer l’invitation au nouveau compte.');
                }

                return $user;
            });
        } catch (Throwable $exception) {
            Log::error('Création ou invitation d’un compte professionnel impossible.', [
                'email' => $validated['email'],
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Le compte n’a pas été créé car son invitation n’a pas pu être envoyée.',
            ], 503);
        }

        return response()->json([
            'message' => 'Compte professionnel créé. Un lien sécurisé a été envoyé par e-mail.',
            'data' => $user,
        ], 201);
    }

    public function resendInvitation(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        if (! in_array($user->role, ['admin', 'pharmacien', 'livreur'], true)) {
            return response()->json(['message' => 'Seuls les comptes professionnels peuvent recevoir une invitation.'], 422);
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (Throwable $exception) {
            Log::error('Échec du renvoi d’invitation.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Le service de messagerie est temporairement indisponible.'], 503);
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Impossible de générer une invitation pour ce compte.'], 503);
        }

        return response()->json(['message' => 'Une nouvelle invitation a été envoyée.']);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Accès réservé à l’administration.');
    }

    private function displayName(array $attributes): string
    {
        if (isset($attributes['prenom'], $attributes['nom'])) {
            return trim($attributes['prenom'].' '.$attributes['nom']);
        }

        return $attributes['name'];
    }
}
