<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('riddles', function (Blueprint $table) {
            $table->foreignId('indice_id')->nullable()->constrained('hints')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riddles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('indice_id');
        });
    }
};
