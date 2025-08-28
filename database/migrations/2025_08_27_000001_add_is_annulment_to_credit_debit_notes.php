<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('credit_debit_notes', function (Blueprint $table) {
            $table->boolean('is_annulment')->default(false)->after('invoice_id');
        });
    }

    public function down()
    {
        Schema::table('credit_debit_notes', function (Blueprint $table) {
            $table->dropColumn('is_annulment');
        });
    }
};
