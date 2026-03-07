<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (Schema::hasColumn('estimates', 'cost_center_id')) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            }

            if (!Schema::hasColumn('estimates', 'cost_center_variety_id')) {
                $table->foreignId('cost_center_variety_id')
                    ->after('estimate_status_id')
                    ->constrained('cost_center_varieties')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['cost_center_variety_id']);
            $table->dropColumn('cost_center_variety_id');

            $table->foreignId('cost_center_id')
                ->after('estimate_status_id')
                ->constrained('cost_centers')
                ->cascadeOnDelete();
        });
    }
};
