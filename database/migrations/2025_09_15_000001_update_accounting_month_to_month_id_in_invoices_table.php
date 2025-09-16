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
            $table->dropColumn('accounting_month');
            $table->unsignedBigInteger('month_id')->nullable()->after('due_date');
            $table->foreign('month_id')->references('id')->on('months')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['month_id']);
            $table->dropColumn('month_id');
            $table->string('accounting_month', 7)->nullable()->after('due_date');
        });
    }
};
