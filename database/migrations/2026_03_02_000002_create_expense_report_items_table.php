<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_report_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('product_id')->nullable()->constrained();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('receipt_path')->nullable()->comment('Ruta foto/PDF boleta');
            $table->json('ocr_data')->nullable()->comment('Datos extraidos por OCR');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete()->comment('FK al invoice cuando se contabiliza');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_report_items');
    }
};
