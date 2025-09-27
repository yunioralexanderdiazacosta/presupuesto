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
        Schema::create('fuel_outflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('machinery_id');
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('cost_center_id');
            $table->string('fuel_type');
            $table->decimal('liters', 10, 2);
            $table->decimal('horometer', 10, 2)->nullable();
            $table->decimal('odometer', 10, 2)->nullable();
            $table->date('date');
            $table->text('observations')->nullable();
            $table->timestamps();
            // Foreign keys (descomenta y ajusta según tus modelos)
            // $table->foreign('team_id')->references('id')->on('teams');
            // $table->foreign('season_id')->references('id')->on('seasons');
            // $table->foreign('machinery_id')->references('id')->on('machineries');
            // $table->foreign('operator_id')->references('id')->on('users');
            // $table->foreign('cost_center_id')->references('id')->on('cost_centers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_outflows');
    }
};
