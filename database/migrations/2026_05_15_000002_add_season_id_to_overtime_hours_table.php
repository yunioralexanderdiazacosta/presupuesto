<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_hours', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->after('team_id')
                ->constrained('seasons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('overtime_hours', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });
    }
};
