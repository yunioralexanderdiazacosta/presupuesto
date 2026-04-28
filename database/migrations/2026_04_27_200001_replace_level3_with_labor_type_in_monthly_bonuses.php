<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_bonuses', function (Blueprint $table) {
            $table->dropForeign(['level3_id']);
            $table->dropColumn('level3_id');
            $table->foreignId('labor_type_id')->after('cost_center_id')->constrained('labor_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_bonuses', function (Blueprint $table) {
            $table->dropForeign(['labor_type_id']);
            $table->dropColumn('labor_type_id');
            $table->foreignId('level3_id')->after('cost_center_id')->constrained('level3s')->restrictOnDelete();
        });
    }
};
