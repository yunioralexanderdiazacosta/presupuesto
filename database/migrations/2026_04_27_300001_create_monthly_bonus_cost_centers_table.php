<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_bonus_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_bonus_id')->constrained('monthly_bonuses')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['monthly_bonus_id', 'cost_center_id'], 'mbcc_bonus_cc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_bonus_cost_centers');
    }
};
