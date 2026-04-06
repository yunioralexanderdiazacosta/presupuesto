<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_orders', function (Blueprint $table) {
            $table->foreignId('tractor_id')->nullable()->after('phenological_stage_id')->constrained('machineries')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->after('tractor_id')->constrained('machineries')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->after('equipment_id')->constrained('operators')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application_orders', function (Blueprint $table) {
            $table->dropForeign(['tractor_id']);
            $table->dropColumn('tractor_id');
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
            $table->dropForeign(['operator_id']);
            $table->dropColumn('operator_id');
        });
    }
};
