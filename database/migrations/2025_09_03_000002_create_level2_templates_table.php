<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level2_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level1_template_id')->constrained('level1_templates')->onDelete('cascade');
            $table->string('name');
            $table->integer('order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level2_templates');
    }
};
