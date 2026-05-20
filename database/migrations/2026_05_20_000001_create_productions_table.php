<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('fruit_id')->constrained('fruits')->cascadeOnDelete();
            $table->decimal('discount', 12, 2)->default(0)->comment('Descuento global aplicado al retorno (USD)');
            $table->decimal('advance', 12, 2)->default(0)->comment('Abono ya recibido a cuenta del retorno (USD)');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['season_id', 'team_id', 'fruit_id'], 'unique_production');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
