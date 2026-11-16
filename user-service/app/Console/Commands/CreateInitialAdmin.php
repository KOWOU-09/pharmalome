<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateInitialAdmin extends Command
{
    protected $signature = 'users:create-admin {--name=} {--email=}';

    protected $description = 'Créer le premier compte administrateur';

    public function handle(): int
    {
        $name = $this->option('name') ?: env('INITIAL_ADMIN_NAME');
        $email = $this->option('email') ?: env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (User::where('role', 'admin')->exists()) {
            $this->info('Un compte administrateur existe déjà.');
            return self::SUCCESS;
        }

        if (!$name || !$email || !$password) {
            $this->error('Les informations du compte administrateur sont incomplètes.');
            return self::FAILURE;
        }

        $validated = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'actif' => true,
        ]);

        $this->info('Compte administrateur créé avec succès.');

        return self::SUCCESS;
    }
}
