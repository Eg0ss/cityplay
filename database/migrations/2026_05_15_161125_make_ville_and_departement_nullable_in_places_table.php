<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->string('ville')->nullable()->change();
            $table->string('departement')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->string('ville')->nullable(false)->change();
            $table->string('departement')->nullable(false)->change();
        });
    }
};
