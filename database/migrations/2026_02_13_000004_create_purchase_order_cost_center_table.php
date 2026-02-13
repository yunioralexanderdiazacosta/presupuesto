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
        Schema::create('purchase_order_cost_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');
            $table->timestamps();
            
            // Índices
            $table->index('purchase_order_id');
            $table->index('cost_center_id');
            
            // Evitar duplicados
            $table->unique(['purchase_order_id', 'cost_center_id'], 'po_cc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_cost_center');
    }
};
