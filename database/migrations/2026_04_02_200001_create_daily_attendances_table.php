<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->boolean('is_present')->default(true);
            $table->foreignId('estimated_labor_type_id')->nullable()->constrained('labor_types')->nullOnDelete();
            $table->foreignId('estimated_cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['employee_id', 'date', 'team_id'], 'unique_attendance_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_attendances');
    }
};
