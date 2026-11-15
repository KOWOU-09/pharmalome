<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $constraints = DB::select(<<<'SQL'
                SELECT conname
                FROM pg_constraint
                WHERE conrelid = 'users'::regclass
                  AND contype = 'c'
                  AND pg_get_constraintdef(oid) ILIKE '%role%'
            SQL);

            foreach ($constraints as $constraint) {
                DB::statement('ALTER TABLE users DROP CONSTRAINT '.$this->quoteIdentifier($constraint->conname));
            }

            DB::statement('ALTER TABLE users ALTER COLUMN role DROP DEFAULT');
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE varchar(255) USING role::text');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'client'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('qualification')->nullable();
            $table->string('numero_ordre')->nullable();
            $table->boolean('pharmacien_verifie')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nom',
                'prenom',
                'qualification',
                'numero_ordre',
                'pharmacien_verifie',
            ]);
        });
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
