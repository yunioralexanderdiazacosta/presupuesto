<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_hour_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_hour_id')->constrained('overtime_hours')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->cascadeOnDelete();
            $table->unique(['overtime_hour_id', 'cost_center_id'], 'ohcc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_hour_cost_centers');
    }
};
