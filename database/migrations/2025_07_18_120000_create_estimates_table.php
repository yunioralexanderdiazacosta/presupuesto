<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->string('estimate_name');
            $table->integer('kilos_ha');
            $table->unsignedBigInteger('cost_center_id');
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('team_id');
            $table->timestamps();

            $table->foreign('cost_center_id')->references('id')->on('cost_centers')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('estimates');
    }
};
