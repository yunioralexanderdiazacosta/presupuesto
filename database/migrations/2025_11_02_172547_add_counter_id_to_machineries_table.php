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
        Schema::table('machineries', function (Blueprint $table) {
            // Agregar counter_id como nullable porque puede haber maquinarias sin contador
            if (!Schema::hasColumn('machineries', 'counter_id')) {
                $table->foreignId('counter_id')->nullable()->after('type_machinery_id')->constrained('counters')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            if (Schema::hasColumn('machineries', 'counter_id')) {
                $table->dropForeign(['counter_id']);
                $table->dropColumn('counter_id');
            }
        });
    }
};
