<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            if (!Schema::hasColumn('machineries', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('team_id')->constrained('branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            if (Schema::hasColumn('machineries', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
