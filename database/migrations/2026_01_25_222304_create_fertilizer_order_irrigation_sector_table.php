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
        Schema::create('fertilizer_order_irrigation_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fertilizer_order_id')->constrained('fertilizer_orders')->onDelete('cascade');
            $table->foreignId('irrigation_sector_id')->constrained('irrigation_sectors')->onDelete('cascade');
            $table->decimal('surface', 10, 2)->comment('Superficie del sector (denormalizada para histórico)');
            $table->timestamps();
            
            $table->index('fertilizer_order_id');
            $table->index('irrigation_sector_id');
            $table->unique(['fertilizer_order_id', 'irrigation_sector_id'], 'fert_order_sector_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fertilizer_order_irrigation_sector');
    }
};
