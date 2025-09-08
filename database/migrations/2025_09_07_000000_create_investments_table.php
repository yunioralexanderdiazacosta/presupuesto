<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('month_execute'); // número de mes (1-12)
            $table->string('estado')->default('planificada');
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->unsignedBigInteger('season_id')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->foreign('responsable_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('season_id')->references('id')->on('seasons')->nullOnDelete();
        });

        Schema::create('cost_center_investment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investment_id');
            $table->unsignedBigInteger('cost_center_id');
            $table->timestamps();

            $table->foreign('investment_id')->references('id')->on('investments')->onDelete('cascade');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
            $table->unique(['investment_id', 'cost_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_center_investment');
        Schema::dropIfExists('investments');
    }
};
