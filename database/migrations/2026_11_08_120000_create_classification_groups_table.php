<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('fruit_id')->constrained('fruits')->cascadeOnDelete();
            $table->string('classification_type'); // caliber, color, quality, ...
            $table->string('name');
            $table->json('values'); // valores del catálogo que componen el grupo
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_groups');
    }
};
