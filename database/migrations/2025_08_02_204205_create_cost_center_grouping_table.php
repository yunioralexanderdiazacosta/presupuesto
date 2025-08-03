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
      Schema::create('cost_center_grouping', function (Blueprint $table) {
        $table->id();
        $table->foreignId('grouping_id')->constrained()->cascadeOnDelete();
        $table->foreignId('cost_center_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_center_grouping');
    }
};
