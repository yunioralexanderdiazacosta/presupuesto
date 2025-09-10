<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['invoice_product_id']);
            $table->foreign('invoice_product_id')
                ->references('id')->on('invoice_product')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['invoice_product_id']);
            $table->foreign('invoice_product_id')
                ->references('id')->on('invoice_product')
                ->onDelete('cascade');
        });
    }
};
