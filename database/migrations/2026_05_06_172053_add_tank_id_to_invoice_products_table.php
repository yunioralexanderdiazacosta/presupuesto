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
        Schema::table('invoice_products', function (Blueprint $table) {
            // La columna puede ya existir si una migración anterior falló a mitad
            if (!Schema::hasColumn('invoice_products', 'tank_id')) {
                $table->foreignId('tank_id')->nullable()->after('branch_id')
                    ->constrained('fuel_tanks')->onDelete('set null');
            } else {
                // Columna existe pero sin FK — solo agregar la constraint
                $table->foreign('tank_id')->references('id')->on('fuel_tanks')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropForeign(['tank_id']);
            $table->dropColumn('tank_id');
        });
    }
};
