<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('statut')->default('en_attente');
            $table->enum('mode_reception', ['retrait', 'livraison'])->default('retrait');
            $table->text('adresse_livraison')->nullable();
            $table->integer('sous_total')->default(0);
            $table->integer('frais_livraison')->default(0);
            $table->integer('total')->default(0);
            $table->timestamps();
        });

        Schema::create('ligne_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->unsignedBigInteger('medicament_id');
            $table->string('nom_medicament');
            $table->integer('prix_unitaire');
            $table->integer('quantite');
        });

        Schema::create('ordonnances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->string('fichier')->nullable();
            $table->enum('statut', ['en_attente', 'validee', 'refusee'])->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->unsignedBigInteger('pharmacien_id')->nullable();
            $table->timestamp('validee_le')->nullable();
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->enum('mode', ['mobile_money', 'a_la_reception'])->default('mobile_money');
            $table->integer('montant');
            $table->enum('statut', ['en_attente', 'payee', 'echouee'])->default('en_attente');
            $table->string('reference');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->boolean('lu')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('ordonnances');
        Schema::dropIfExists('ligne_commandes');
        Schema::dropIfExists('commandes');
    }
};
