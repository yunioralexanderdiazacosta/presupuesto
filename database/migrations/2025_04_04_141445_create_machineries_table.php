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
        Schema::create('machineries', function (Blueprint $table) {
            $table->id();
             $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('type_machinery_id')->nullable()->constrained('type_machineries')->cascadeOnDelete();
            $table->foreignId('company_reason_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('cod_machinery');
            $table->integer('volume');
            $table->boolean('is_active')->default(false);
            $table->text('brand');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machineries');
    }
};
