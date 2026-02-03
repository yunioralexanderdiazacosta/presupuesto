<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credit_debit_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('month_id')->nullable()->after('date');
            $table->foreign('month_id')->references('id')->on('months');
        });
    }

    public function down()
    {
        Schema::table('credit_debit_notes', function (Blueprint $table) {
            $table->dropForeign(['month_id']);
            $table->dropColumn('month_id');
        });
    }
};
