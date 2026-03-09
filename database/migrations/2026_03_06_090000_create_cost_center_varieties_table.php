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
        Schema::create('cost_center_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->foreignId('fruit_id')->constrained('fruits')->cascadeOnDelete();
            $table->foreignId('rootstock_id')->nullable()->constrained('rootstocks')->nullOnDelete();
            $table->foreignId('development_state_id')->nullable()->constrained('development_states')->nullOnDelete();
            $table->decimal('surface', 8, 2);
            $table->integer('year_plantation')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_center_varieties');
    }
};
