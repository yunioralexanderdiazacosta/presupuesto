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
        Schema::table('daily_yields', function (Blueprint $table) {
            $table->unsignedInteger('target_price')->nullable()->after('bonus_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_yields', function (Blueprint $table) {
            $table->dropColumn('target_price');
        });
    }
};
