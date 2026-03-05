<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_report_items', function (Blueprint $table) {
            $table->foreignId('type_document_id')->nullable()->constrained('type_documents')->nullOnDelete()->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('expense_report_items', function (Blueprint $table) {
            $table->dropForeign(['type_document_id']);
            $table->dropColumn('type_document_id');
        });
    }
};
