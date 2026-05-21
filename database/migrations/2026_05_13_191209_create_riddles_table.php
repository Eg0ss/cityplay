<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riddles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->onDelete('cascade');
            $table->integer('niveau'); // 1, 2, 3
            $table->text('description'); // Le texte de l'énigme
            $table->string('reponse'); // La réponse à l'énigme
            $table->json('mcq_options')->nullable(); // Options QCM pour niveaux 1 & 2
            $table->unsignedBigInteger('indice_id')->nullable(); // Reference à un indice principal (optionnel)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riddles');
    }
};