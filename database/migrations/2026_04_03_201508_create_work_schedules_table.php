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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->decimal('monday_hours', 3, 1)->default(8.0);
            $table->decimal('tuesday_hours', 3, 1)->default(8.0);
            $table->decimal('wednesday_hours', 3, 1)->default(8.0);
            $table->decimal('thursday_hours', 3, 1)->default(8.0);
            $table->decimal('friday_hours', 3, 1)->default(8.0);
            $table->decimal('saturday_hours', 3, 1)->default(0.0);
            $table->decimal('sunday_hours', 3, 1)->default(0.0);
            $table->decimal('weekly_hours', 4, 1)->default(40.0);
            $table->timestamps();

            $table->unique(['team_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
