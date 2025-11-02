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
            // Verificar si la columna no existe antes de agregarla
            if (!Schema::hasColumn('fuel_outflows', 'product_id')) {
                // Agregar columna product_id como nullable primero
                $table->foreignId('product_id')->nullable()->after('operator_id')->constrained('products')->onDelete('cascade');
            }
        });
        
        // Nota: Después de esta migración, debes:
        // 1. Crear productos de combustible en la tabla products con level3 = 'Combustible'
        // 2. Migrar manualmente los datos de fuel_type a product_id
        // 3. Ejecutar una migración adicional para eliminar fuel_type
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Eliminar foreign key y columna product_id
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
