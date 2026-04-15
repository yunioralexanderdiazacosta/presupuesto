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
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('is_active')->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->after('payment_method_id')->constrained('banks')->nullOnDelete();
            $table->foreignId('account_type_id')->nullable()->after('bank_id')->constrained('account_types')->nullOnDelete();
            $table->string('account_number', 30)->nullable()->after('account_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['account_type_id']);
            $table->dropColumn(['payment_method_id', 'bank_id', 'account_type_id', 'account_number']);
        });
    }
};
