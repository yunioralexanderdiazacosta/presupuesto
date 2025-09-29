<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            $table->unsignedBigInteger('level1_id')->nullable()->after('cost_center_id');
            $table->foreign('level1_id')->references('id')->on('level1s')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            $table->dropForeign(['level1_id']);
            $table->dropColumn('level1_id');
        });
    }
};
