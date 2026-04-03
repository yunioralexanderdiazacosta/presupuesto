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
            $table->foreignId('labor_rate_id')->nullable()->after('labor_type_id')->constrained('labor_rates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_yields', function (Blueprint $table) {
            $table->dropForeign(['labor_rate_id']);
            $table->dropColumn('labor_rate_id');
        });
    }
};
