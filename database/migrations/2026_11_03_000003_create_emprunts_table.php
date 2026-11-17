<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprunts', function (Blueprint $table) {
            $table->id();
            // cascade : si un compte est supprimé, ses emprunts partent avec lui
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            // restrict : impossible de supprimer un livre qui a un historique d'emprunts
            $table->foreignId('livre_id')
                  ->constrained('livres')
                  ->restrictOnDelete();
            $table->date('date_emprunt');
            $table->date('date_retour_prevue');
            // NULL = livre pas encore rendu (emprunt en cours)
            $table->date('date_retour_effective')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprunts');
    }
};
