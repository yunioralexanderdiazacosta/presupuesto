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
            // Monto total real capturado del documento (OCR o manual). Puede diferir del
            // cálculo teórico (neto + IVA) cuando la factura trae un ajuste de Impuesto
            // Específico (ej. combustibles), que a veces es negativo (MEPCO).
            $table->decimal('total_amount', 14, 2)->nullable()->after('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};
