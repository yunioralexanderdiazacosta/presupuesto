<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('consumptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cost_center_id');
            $table->date('date');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('operation_id')->nullable();
            $table->unsignedBigInteger('machinary_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('season_id')->references('id')->on('seasons');
            $table->foreign('operation_id')->references('id')->on('operations');
            $table->foreign('machinary_id')->references('id')->on('machineries');
            $table->foreign('project_id')->references('id')->on('projects');
        });

        Schema::create('consumption_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consumption_id');
            $table->unsignedBigInteger('invoice_item_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 2);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->foreign('consumption_id')->references('id')->on('consumptions')->onDelete('cascade');
            $table->foreign('invoice_item_id')->references('id')->on('invoice_product');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down()
    {
        Schema::dropIfExists('consumption_items');
        Schema::dropIfExists('consumptions');
    }
};
