<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminations', function (Blueprint $table) {
            $table->integer('vacation_days')->nullable()->after('settlement');
            $table->integer('indemnification')->nullable()->after('vacation_days');
            $table->integer('notice_month')->nullable()->after('indemnification');
            $table->integer('years_of_service')->nullable()->after('notice_month');
            $table->integer('afc_discount')->nullable()->after('years_of_service');
        });
    }

    public function down(): void
    {
        Schema::table('terminations', function (Blueprint $table) {
            $table->dropColumn(['vacation_days', 'indemnification', 'notice_month', 'years_of_service', 'afc_discount']);
        });
    }
};
