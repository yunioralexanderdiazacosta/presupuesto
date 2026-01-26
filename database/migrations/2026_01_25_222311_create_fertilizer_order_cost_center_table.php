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
        Schema::create('fertilizer_order_cost_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fertilizer_order_id')->constrained('fertilizer_orders')->onDelete('cascade');
            $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('fertilizer_order_id');
            $table->index('cost_center_id');
            $table->unique(['fertilizer_order_id', 'cost_center_id'], 'fert_order_cc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fertilizer_order_cost_center');
    }
};
