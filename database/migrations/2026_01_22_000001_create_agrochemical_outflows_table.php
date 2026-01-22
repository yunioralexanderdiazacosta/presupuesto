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
        Schema::create('agrochemical_outflows', function (Blueprint $table) {
            $table->id();
            
            // Relación con la orden de aplicación
            $table->foreignId('application_order_id')->constrained('application_orders')->cascadeOnDelete();
            
            // Datos de la aplicación
            $table->decimal('maquinadas', 10, 2);
            $table->date('date');
            
            // Producto aplicado
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Origen del stock
            $table->foreignId('invoice_product_id')->nullable()->constrained('invoice_products')->nullOnDelete();
            $table->foreignId('credit_debit_note_item_id')->nullable()->constrained('credit_debit_note_items')->nullOnDelete();
            
            // Cantidad utilizada
            $table->decimal('quantity', 10, 2);
            
            // Centro de costo
            $table->foreignId('cost_center_id')->constrained('cost_centers')->cascadeOnDelete();
            
            // Observaciones
            $table->text('observations')->nullable();
            
            // Equipo y temporada (filtrado crítico)
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            
            $table->timestamps();
            
            // Índices
            $table->index(['team_id', 'season_id']);
            $table->index('application_order_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agrochemical_outflows');
    }
};
