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
            $table->string('payment_type', 10)->default('trato')->after('date'); // 'trato' o 'dia'
        });
    }

    public function down(): void
    {
        Schema::table('daily_yields', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
