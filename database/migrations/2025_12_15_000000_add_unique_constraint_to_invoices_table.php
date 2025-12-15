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
        Schema::table('invoices', function (Blueprint $table) {
            // Agregar índice único para evitar facturas duplicadas
            // Combinación: proveedor + número de documento + equipo
            $table->unique(['supplier_id', 'number_document', 'team_id'], 'invoices_supplier_number_team_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_supplier_number_team_unique');
        });
    }
};
