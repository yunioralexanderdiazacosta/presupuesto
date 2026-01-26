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
        Schema::create('fertilizer_order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fertilizer_order_id')->constrained('fertilizer_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('dosis_por_hectarea', 10, 2)->comment('Dosis por hectárea');
            $table->decimal('cantidad_total', 10, 2)->comment('Cantidad total calculada');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->timestamps();
            
            $table->index('fertilizer_order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fertilizer_order_product');
    }
};
