<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Quien rinde');
            $table->string('number', 20)->comment('Correlativo: RG-001');
            $table->text('description')->nullable();
            $table->enum('status', ['borrador', 'enviada', 'aprobada', 'pagada', 'rechazada'])->default('borrador');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_notes')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'season_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_reports');
    }
};
