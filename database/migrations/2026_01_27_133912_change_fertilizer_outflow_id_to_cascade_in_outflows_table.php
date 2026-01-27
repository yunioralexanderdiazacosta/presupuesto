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
            // Eliminar la foreign key existente
            $table->dropForeign(['fertilizer_outflow_id']);
            
            // Recrear con cascade
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
            // Eliminar la foreign key con cascade
            $table->dropForeign(['fertilizer_outflow_id']);
            
            // Recrear con nullOnDelete
            $table->foreign('fertilizer_outflow_id')
                ->references('id')
                ->on('fertilizer_outflows')
                ->nullOnDelete();
        });
    }
};
