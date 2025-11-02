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
        // Verificar si la columna existe antes de eliminarla
        if (Schema::hasColumn('fuel_outflows', 'product_id')) {
            // Intentar eliminar la foreign key si existe usando DB directo
            try {
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE fuel_outflows DROP FOREIGN KEY fuel_outflows_product_id_foreign');
            } catch (\Exception $e) {
                // Si no existe, continuamos
            }
            
            // Eliminar la columna
            Schema::table('fuel_outflows', function (Blueprint $table) {
                $table->dropColumn('product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows_if_exists', function (Blueprint $table) {
            //
        });
    }
};
