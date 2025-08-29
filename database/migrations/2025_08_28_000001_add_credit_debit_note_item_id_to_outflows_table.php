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
        Schema::table('outflows', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_debit_note_item_id')->nullable()->after('invoice_product_id');
            $table->foreign('credit_debit_note_item_id')->references('id')->on('credit_debit_note_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['credit_debit_note_item_id']);
            $table->dropColumn('credit_debit_note_item_id');
        });
    }
};
