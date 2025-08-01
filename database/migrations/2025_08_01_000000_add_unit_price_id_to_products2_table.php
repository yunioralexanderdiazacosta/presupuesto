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
        Schema::table('products2', function (Blueprint $table) {
            // Añadir columna unit_price_id con clave foránea precisa a units
            $table->foreignId('unit_price_id')
                  ->nullable()
                  ->after('active_ingredient')
                  ->constrained('units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products2', function (Blueprint $table) {
            $table->dropForeign(['unit_price_id']);
            $table->dropColumn('unit_price_id');
        });
    }
};
