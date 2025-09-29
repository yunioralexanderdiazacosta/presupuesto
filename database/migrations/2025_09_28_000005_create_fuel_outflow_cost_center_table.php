<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fuel_outflow_cost_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_outflow_id')->constrained('fuel_outflows')->onDelete('cascade');
            $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fuel_outflow_cost_center');
    }
};
