<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('contracts')
                ->nullOnDelete();
        });

        Schema::table('daily_yields', function (Blueprint $table) {
            $table->foreignId('contract_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('contracts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });

        Schema::table('daily_yields', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });
    }
};
