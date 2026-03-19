<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->decimal('net_kilo', 10, 2)->nullable()->after('kg_exported');
        });
    }

    public function down(): void
    {
        Schema::table('production_summaries', function (Blueprint $table) {
            $table->dropColumn('net_kilo');
        });
    }
};
