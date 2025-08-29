<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('credit_debit_note_items', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_product_id')->nullable()->after('credit_debit_note_id');
            $table->foreign('invoice_product_id')->references('id')->on('invoice_product')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('credit_debit_note_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_product_id']);
            $table->dropColumn('invoice_product_id');
        });
    }
};
