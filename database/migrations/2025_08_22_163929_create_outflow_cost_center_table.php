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
        Schema::create('outflow_cost_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outflow_id')->constrained('outflows')->onDelete('cascade');
            $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outflow_cost_center');
    }
};
