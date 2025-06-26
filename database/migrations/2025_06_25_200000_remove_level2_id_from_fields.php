<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropForeign(['level2_id']);
            $table->dropColumn('level2_id');
        });
    }

    public function down()
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->unsignedBigInteger('level2_id')->nullable();
            $table->foreign('level2_id')->references('id')->on('level2s')->onDelete('set null');
        });
    }
};
