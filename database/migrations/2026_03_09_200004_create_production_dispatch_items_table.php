<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_dispatch_id')->constrained('production_dispatches')->cascadeOnDelete();
            $table->string('classification_type'); // caliber, color, quality
            $table->string('classification_value'); // L, XL, Light, Export, etc.
            $table->decimal('kg', 12, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->integer('boxes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_dispatch_items');
    }
};
