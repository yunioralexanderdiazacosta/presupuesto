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
        Schema::table('harvests', function (Blueprint $table) {
                $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
    $table->foreignId('season_id')->nullable()->constrained('seasons')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
              $table->dropForeign(['team_id']);
    $table->dropColumn('team_id');
    $table->dropForeign(['season_id']);
    $table->dropColumn('season_id');
        });
    }
};
