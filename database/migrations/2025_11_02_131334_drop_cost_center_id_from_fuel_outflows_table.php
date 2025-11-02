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
            // Verificar si la columna existe antes de eliminarla
            if (Schema::hasColumn('fuel_outflows', 'cost_center_id')) {
                $table->dropColumn('cost_center_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Restaurar la columna como nullable por si se necesita revertir
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('operator_id');
        });
    }
};
