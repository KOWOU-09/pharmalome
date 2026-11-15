<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('libelle')->unique();
            $table->timestamps();
        });

        Schema::create('medicaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->string('nom');
            $table->string('dosage')->nullable();
            $table->string('laboratoire')->nullable();
            $table->text('description')->nullable();
            $table->integer('prix');
            $table->integer('quantite_stock')->default(0);
            $table->integer('seuil_alerte')->default(10);
            $table->date('date_peremption')->nullable();
            $table->boolean('ordonnance_requise')->default(false);
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicaments');
        Schema::dropIfExists('categories');
    }
};
