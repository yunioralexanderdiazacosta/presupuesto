<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variety_cost_params', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->decimal('pct_embalaje', 5, 2)->default(85); // % embalaje
            $table->decimal('precio_proceso', 8, 4)->default(0); // USD/kg
            $table->timestamps();

            $table->unique(['team_id', 'variety_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variety_cost_params');
    }
};
