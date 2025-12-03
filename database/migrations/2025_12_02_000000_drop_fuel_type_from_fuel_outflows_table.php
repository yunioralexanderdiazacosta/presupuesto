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
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Eliminar la columna fuel_type ya que product_id la reemplaza
            if (Schema::hasColumn('fuel_outflows', 'fuel_type')) {
                $table->dropColumn('fuel_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Recrear la columna en caso de rollback
            $table->string('fuel_type')->nullable();
        });
    }
};
