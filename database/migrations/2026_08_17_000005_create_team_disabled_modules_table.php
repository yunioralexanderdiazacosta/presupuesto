<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_disabled_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->timestamps();

            $table->unique(['team_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_disabled_modules');
    }
};
