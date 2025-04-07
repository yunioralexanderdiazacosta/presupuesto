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
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('season_id')->nullable()->constrained('seasons')->cascadeOnDelete();
            $table->dropColumn('season');
            $table->dropForeign(['month_id']);
            $table->dropColumn('month_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
            $table->text('season');
            $table->foreignId('month_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
