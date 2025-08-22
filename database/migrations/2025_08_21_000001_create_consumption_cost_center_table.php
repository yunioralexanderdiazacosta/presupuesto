<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('consumption_cost_center', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consumption_id');
            $table->unsignedBigInteger('cost_center_id');
            $table->timestamps();

            $table->foreign('consumption_id')->references('id')->on('consumptions')->onDelete('cascade');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('consumption_cost_center');
    }
};
