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
            // Agregar FK a fuel_outflows (opcional, solo si la salida es de combustible)
            $table->foreignId('fuel_outflow_id')
                ->nullable()
                ->after('credit_debit_note_item_id')
                ->constrained('fuel_outflows')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['fuel_outflow_id']);
            $table->dropColumn('fuel_outflow_id');
        });
    }
};
