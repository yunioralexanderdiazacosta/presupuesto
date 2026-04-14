<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->decimal('commercial_cost_per_kg', 10, 2)->nullable()->default(0)->after('net_kilo');
        });
    }

    public function down(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropColumn('commercial_cost_per_kg');
        });
    }
};
