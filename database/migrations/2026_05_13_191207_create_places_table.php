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
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            $table->string('nom');
            $table->string('ville')->nullable();
            $table->string('departement')->nullable();
            $table->text('verified_description')->nullable();
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->integer('rayon_marge')->default(50);
            $table->integer('marge_validation_gps')->nullable()->default(10)->comment('Marge en mètres pour la validation GPS');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};