<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('month_id')->constrained('months')->restrictOnDelete();
            $table->date('date');
            $table->foreignId('labor_type_id')->constrained('labor_types')->restrictOnDelete();
            $table->foreignId('overtime_type_id')->constrained('overtime_types')->restrictOnDelete();
            $table->decimal('hours', 5, 2);
            $table->text('observations')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_hours');
    }
};
