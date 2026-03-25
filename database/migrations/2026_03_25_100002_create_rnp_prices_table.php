<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnp_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->unsignedTinyInteger('week'); // 42-52
            $table->decimal('price_usd', 8, 4)->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'variety_id', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnp_prices');
    }
};
