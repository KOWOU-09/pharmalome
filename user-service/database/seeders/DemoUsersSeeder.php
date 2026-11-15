<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $numberOfUsers = filter_var(env('SEED_DEMO_USERS', 0), FILTER_VALIDATE_INT);

        if ($numberOfUsers === false || $numberOfUsers < 1) {
            return;
        }

        $password = Hash::make(Str::random(40));
        $batchSize = 500;

        for ($start = 1; $start <= $numberOfUsers; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $numberOfUsers);
            $timestamp = now();
            $users = [];

            for ($number = $start; $number <= $end; $number++) {
                $users[] = [
                    'name' => "Client démo {$number}",
                    'email' => "client{$number}@demo.pharmalome.tg",
                    'password' => $password,
                    'role' => 'client',
                    'actif' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            DB::table('users')->insertOrIgnore($users);
        }
    }
}
