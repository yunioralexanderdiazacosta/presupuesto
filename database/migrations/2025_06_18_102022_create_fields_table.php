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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->foreignId('level2_id')->constrained('level2s')->onDelete('cascade');
            $table->foreignId('subfamily_id')->constrained('level3s')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity');
            $table->integer('price');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
