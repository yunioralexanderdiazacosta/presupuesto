<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('monthly_bonus_type_id')->constrained('monthly_bonus_types')->restrictOnDelete();
            $table->foreignId('month_id')->constrained('months')->restrictOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->restrictOnDelete();
            $table->foreignId('level3_id')->constrained('level3s')->restrictOnDelete();
            $table->integer('amount');
            $table->text('observations')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_bonuses');
    }
};
