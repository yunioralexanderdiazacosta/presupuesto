<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level3_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level2_template_id')->constrained('level2_templates')->onDelete('cascade');
            $table->string('name');
            $table->integer('order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level3_templates');
    }
};
