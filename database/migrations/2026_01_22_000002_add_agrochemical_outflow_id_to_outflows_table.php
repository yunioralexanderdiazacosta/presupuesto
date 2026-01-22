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
            $table->foreignId('agrochemical_outflow_id')
                ->nullable()
                ->after('fuel_outflow_id')
                ->constrained('agrochemical_outflows')
                ->nullOnDelete();
            
            $table->index('agrochemical_outflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['agrochemical_outflow_id']);
            $table->dropIndex(['agrochemical_outflow_id']);
            $table->dropColumn('agrochemical_outflow_id');
        });
    }
};
