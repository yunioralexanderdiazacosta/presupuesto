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
        Schema::table('groupings', function (Blueprint $table) {
            $table->dropForeign(['costcenter_id']);
            $table->dropColumn('costcenter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groupings', function (Blueprint $table) {
            $table->foreignId('costcenter_id')
                ->constrained('cost_centers')
                ->cascadeOnDelete();
        });
    }
};
