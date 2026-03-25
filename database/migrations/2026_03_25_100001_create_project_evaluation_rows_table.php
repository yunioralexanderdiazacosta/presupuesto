<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_evaluation_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_evaluation_id')->constrained('project_evaluations')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->unsignedTinyInteger('week'); // semana de cosecha (42-52)
            $table->decimal('hectares', 8, 2);
            $table->decimal('kg_pessimistic', 10, 2)->default(0);
            $table->decimal('kg_base', 10, 2)->default(0);
            $table->decimal('kg_optimistic', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_evaluation_rows');
    }
};
