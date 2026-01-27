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
        Schema::table('outflows', function (Blueprint $table) {
            // Eliminar foreign keys existentes
            $table->dropForeign(['agrochemical_outflow_id']);
            $table->dropForeign(['fertilizer_outflow_id']);
            
            // Recrear con onDelete('cascade')
            $table->foreign('agrochemical_outflow_id')
                ->references('id')
                ->on('agrochemical_outflows')
                ->onDelete('cascade');
            
            $table->foreign('fertilizer_outflow_id')
                ->references('id')
                ->on('fertilizer_outflows')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            // Eliminar foreign keys con cascade
            $table->dropForeign(['agrochemical_outflow_id']);
            $table->dropForeign(['fertilizer_outflow_id']);
            
            // Recrear con nullOnDelete
            $table->foreign('agrochemical_outflow_id')
                ->references('id')
                ->on('agrochemical_outflows')
                ->nullOnDelete();
            
            $table->foreign('fertilizer_outflow_id')
                ->references('id')
                ->on('fertilizer_outflows')
                ->nullOnDelete();
        });
    }
};
