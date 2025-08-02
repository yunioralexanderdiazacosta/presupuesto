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
        Schema::table('cost_centers', function (Blueprint $table) {
            // Agregar llave foránea grouping_id
            $table->foreignId('grouping_id')
                  ->nullable()
                  ->constrained('groupings')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            //
        });
    }
};
