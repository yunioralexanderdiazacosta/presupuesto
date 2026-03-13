<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('second_name')->nullable();
            $table->string('paternal_surname');
            $table->string('maternal_surname')->nullable();
            $table->string('rut', 12)->unique();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 60)->default('Chilena');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
