<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_yields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->foreignId('labor_type_id')->constrained('labor_types')->onDelete('cascade');
            $table->integer('rate')->default(0);
            $table->decimal('quantity', 10, 2)->default(0);
            $table->integer('amount')->default(0);
            $table->decimal('hours', 4, 1)->default(0);
            $table->foreignId('bonus_type_id')->nullable()->constrained('bonus_types')->nullOnDelete();
            $table->integer('bonus_amount')->default(0);
            $table->foreignId('cost_center_id')->constrained('cost_centers')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date']);
            $table->index(['date', 'team_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_yields');
    }
};
