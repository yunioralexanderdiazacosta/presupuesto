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
        Schema::create('fertilizer_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('irrigation_pump_id')->nullable()->constrained('irrigation_pumps')->nullOnDelete();
            $table->string('responsable')->nullable();
            $table->text('observations')->nullable();
            $table->enum('status', ['pendiente', 'completada', 'cancelada'])->default('pendiente');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['team_id', 'season_id']);
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fertilizer_orders');
    }
};
