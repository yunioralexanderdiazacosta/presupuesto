<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fruit_classification_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fruit_id')->constrained('fruits')->cascadeOnDelete();
            $table->string('type'); // caliber, color, quality
            $table->string('value'); // L, XL, 2J, Light, Dark, Export, etc.
            $table->integer('sort_order')->default(0);
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fruit_classification_types');
    }
};
