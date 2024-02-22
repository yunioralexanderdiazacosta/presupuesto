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
        Schema::create('agrochemicals', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('dose_type');
            $table->decimal('dose');
            $table->integer('price');
            $table->integer('mojamiento');
            $table->text('observations')->nullable();
            $table->foreignId('subfamily_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agrochemicals');
    }
};
