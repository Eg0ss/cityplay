<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riddle_id')->constrained('riddles')->onDelete('cascade');
            $table->enum('type', ['text', 'image', 'keyword', 'description']); // Type d'indice
            $table->text('content'); // Contenu de l'indice
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('easy'); // Niveau de difficulté
            $table->integer('order')->default(1); // Ordre d'affichage des indices
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hints');
    }
};
