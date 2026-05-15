<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('places');
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ville');
            $table->string('departement');
            $table->text('verified_description');
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->integer('rayon_marge')->default(5); // en mètres
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};