<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_report_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->string('product_name')->nullable()->after('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('expense_report_items', function (Blueprint $table) {
            $table->dropColumn('product_name');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
