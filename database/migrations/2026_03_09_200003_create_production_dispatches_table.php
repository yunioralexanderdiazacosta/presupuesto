<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_center_variety_id')->constrained('cost_center_varieties')->cascadeOnDelete();
            $table->foreignId('exporter_id')->constrained('exporters')->cascadeOnDelete();
            $table->foreignId('packing_house_id')->constrained('packing_houses')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->date('dispatch_date');
            $table->date('process_date')->nullable();
            $table->string('guide_number');
            $table->decimal('kg_dispatched', 12, 2);
            $table->decimal('kg_received', 12, 2)->nullable();
            $table->decimal('kg_exported', 12, 2)->nullable();
            $table->decimal('kg_national', 12, 2)->nullable();
            $table->decimal('kg_industrial', 12, 2)->nullable();
            $table->decimal('kg_waste', 12, 2)->nullable();
            $table->integer('bins_dispatched')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('carrier')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_dispatches');
    }
};
