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
            $table->foreignId('fertilizer_outflow_id')
                ->nullable()
                ->after('agrochemical_outflow_id')
                ->constrained('fertilizer_outflows')
                ->nullOnDelete();
            
            $table->index('fertilizer_outflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['fertilizer_outflow_id']);
            $table->dropIndex(['fertilizer_outflow_id']);
            $table->dropColumn('fertilizer_outflow_id');
        });
    }
};
