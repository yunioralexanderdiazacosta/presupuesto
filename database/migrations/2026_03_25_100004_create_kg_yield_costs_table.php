<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kg_yield_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedInteger('kg_ha'); // ej: 0, 1000, 2000, ...
            $table->decimal('cost_usd', 8, 4)->default(0); // costo USD/kg para ese rendimiento
            $table->timestamps();

            $table->unique(['team_id', 'kg_ha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kg_yield_costs');
    }
};
