<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['afp', 'health_plan']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('afp_id')->nullable()->after('net_salary')->constrained('afps')->nullOnDelete();
            $table->foreignId('health_plan_id')->nullable()->after('afp_id')->constrained('health_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['afp_id']);
            $table->dropForeign(['health_plan_id']);
            $table->dropColumn(['afp_id', 'health_plan_id']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->string('afp', 60)->nullable()->after('net_salary');
            $table->string('health_plan', 60)->nullable()->after('afp');
        });
    }
};
