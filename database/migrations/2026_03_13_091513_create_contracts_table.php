<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_reason_id')->constrained('company_reasons')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->date('contract_date');
            $table->string('contract_type', 30); // Faena, Plazo Fijo, Indefinido
            $table->string('position', 150)->nullable(); // Cargo
            $table->string('labor', 150)->nullable(); // Labor
            $table->decimal('base_salary', 12, 0)->default(0); // Sueldo base CLP
            $table->decimal('net_salary', 12, 0)->default(0); // Sueldo líquido CLP
            $table->string('afp', 60)->nullable();
            $table->string('health_plan', 60)->nullable(); // Fonasa/Isapre
            $table->string('city', 100)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 150)->nullable();
            $table->date('end_date')->nullable(); // Solo para Plazo Fijo
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
