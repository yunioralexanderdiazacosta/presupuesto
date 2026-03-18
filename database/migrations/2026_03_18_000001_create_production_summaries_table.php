<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_center_variety_id')->constrained('cost_center_varieties')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->decimal('kg_harvested', 12, 2)->default(0);
            $table->decimal('kg_exported', 12, 2)->nullable()->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['cost_center_variety_id', 'season_id', 'team_id'], 'unique_production_summary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_summaries');
    }
};
