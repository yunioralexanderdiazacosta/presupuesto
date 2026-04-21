<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('causales_termino', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);           // Ej: "159-4", "160", "mutuo_acuerdo"
            $table->string('nombre', 150);           // Descripción completa del causal
            $table->string('articulo', 60)->nullable(); // Ej: "Art. 159 N°4"
            $table->boolean('aplica_faena')->default(true);
            $table->boolean('activa')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('causales_termino');
    }
};
