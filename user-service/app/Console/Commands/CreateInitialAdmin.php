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

    protected $description = 'Crée de façon interactive le premier compte administrateur';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('Un compte administrateur existe déjà.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Nom complet');
        $email = $this->option('email') ?: $this->ask('Adresse e-mail');
        $password = $this->secret('Mot de passe (8 caractères minimum)');

        $validated = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        if ($password !== $this->secret('Confirmer le mot de passe')) {
            $this->error('Les mots de passe ne correspondent pas.');

            return self::FAILURE;
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'actif' => true,
        ]);

        $this->info('Le compte administrateur initial a été créé.');

        return self::SUCCESS;
    }
}
