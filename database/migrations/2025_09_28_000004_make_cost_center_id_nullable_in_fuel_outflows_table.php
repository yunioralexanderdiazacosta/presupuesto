<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_id')->nullable(false)->change();
        });
    }
};
