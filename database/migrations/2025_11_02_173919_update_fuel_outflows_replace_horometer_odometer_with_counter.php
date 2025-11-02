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
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Eliminar columnas horometer y odometer
            if (Schema::hasColumn('fuel_outflows', 'horometer')) {
                $table->dropColumn('horometer');
            }
            if (Schema::hasColumn('fuel_outflows', 'odometer')) {
                $table->dropColumn('odometer');
            }
            
            // Agregar counter_id y counter_value
            if (!Schema::hasColumn('fuel_outflows', 'counter_id')) {
                $table->foreignId('counter_id')->nullable()->after('machinery_id')->constrained('counters')->onDelete('set null');
            }
            if (!Schema::hasColumn('fuel_outflows', 'counter_value')) {
                $table->decimal('counter_value', 10, 2)->nullable()->after('counter_id')->comment('Valor del contador (horómetro u odómetro)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Restaurar columnas horometer y odometer
            $table->decimal('horometer', 10, 2)->nullable()->after('liters');
            $table->decimal('odometer', 10, 2)->nullable()->after('horometer');
            
            // Eliminar counter_id y counter_value
            if (Schema::hasColumn('fuel_outflows', 'counter_id')) {
                $table->dropForeign(['counter_id']);
                $table->dropColumn('counter_id');
            }
            if (Schema::hasColumn('fuel_outflows', 'counter_value')) {
                $table->dropColumn('counter_value');
            }
        });
    }
};
