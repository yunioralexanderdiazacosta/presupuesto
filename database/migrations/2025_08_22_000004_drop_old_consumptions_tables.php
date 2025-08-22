<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('consumption_items');
        Schema::dropIfExists('consumption_cost_center');
        Schema::dropIfExists('consumptions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se recrean las tablas eliminadas
    }
};
