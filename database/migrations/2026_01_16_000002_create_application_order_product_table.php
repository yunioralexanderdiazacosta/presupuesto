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
        Schema::create('application_order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_order_id')->constrained('application_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // Tipo de dosis (excluyente)
            $table->enum('tipo_dosis', ['por_hectarea', 'por_100_litros']);
            
            // Dosis (solo una será usada según tipo_dosis)
            $table->decimal('dosis_por_100', 10, 2)->nullable()->comment('Dosis por 100 litros');
            $table->decimal('dosis_por_hectarea', 10, 2)->nullable()->comment('Dosis por hectárea');
            
            // Cantidades calculadas
            $table->decimal('cantidad_por_hectarea', 10, 2)->comment('Cantidad calculada por hectárea (L/ha)');
            $table->decimal('cantidad_total', 10, 2)->comment('Cantidad total necesaria');
            
            // Días de carencia y reingreso
            $table->integer('carencia')->comment('Días de carencia antes de cosecha');
            $table->integer('reingreso')->comment('Días de reingreso al campo');
            
            $table->timestamps();
            
            // Índices
            $table->index('application_order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_order_product');
    }
};
