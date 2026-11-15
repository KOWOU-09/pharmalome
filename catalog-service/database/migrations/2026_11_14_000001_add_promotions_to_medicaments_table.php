<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->boolean('promotion')->default(false);
            $table->unsignedInteger('prix_promotion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->dropColumn(['promotion', 'prix_promotion']);
        });
    }
};
