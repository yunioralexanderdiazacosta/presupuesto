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
        Schema::table('outflows', function (Blueprint $table) {
            $table->unsignedBigInteger('fuel_outflow_id')->nullable()->after('level3_id');
            $table->foreign('fuel_outflow_id')->references('id')->on('fuel_outflows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['fuel_outflow_id']);
            $table->dropColumn('fuel_outflow_id');
        });
    }
};
