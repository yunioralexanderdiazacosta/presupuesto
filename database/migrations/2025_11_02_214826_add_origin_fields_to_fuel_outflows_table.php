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
            // Agregar campos de origen (de dónde proviene el combustible)
            $table->unsignedBigInteger('invoice_product_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('credit_debit_note_item_id')->nullable()->after('invoice_product_id');
            
            // Foreign keys
            $table->foreign('invoice_product_id')
                ->references('id')
                ->on('invoice_product')
                ->onDelete('set null');
            
            $table->foreign('credit_debit_note_item_id')
                ->references('id')
                ->on('credit_debit_note_items')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_outflows', function (Blueprint $table) {
            // Eliminar foreign keys primero
            $table->dropForeign(['invoice_product_id']);
            $table->dropForeign(['credit_debit_note_item_id']);
            
            // Eliminar columnas
            $table->dropColumn(['invoice_product_id', 'credit_debit_note_item_id']);
        });
    }
};
