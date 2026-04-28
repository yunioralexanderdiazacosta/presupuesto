<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_hours', function (Blueprint $table) {
            $table->integer('base_salary_snapshot')->nullable()->after('hours')
                ->comment('Sueldo base del contrato al momento del registro');
            $table->decimal('hourly_rate_factor_snapshot', 10, 7)->nullable()->after('base_salary_snapshot')
                ->comment('Factor hora ordinaria del tipo HE al momento del registro');
            $table->decimal('overtime_multiplier_snapshot', 5, 2)->nullable()->after('hourly_rate_factor_snapshot')
                ->comment('Multiplicador recargo del tipo HE al momento del registro');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_hours', function (Blueprint $table) {
            $table->dropColumn(['base_salary_snapshot', 'hourly_rate_factor_snapshot', 'overtime_multiplier_snapshot']);
        });
    }
};
