<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('season_id')->references('id')->on('seasons');
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
