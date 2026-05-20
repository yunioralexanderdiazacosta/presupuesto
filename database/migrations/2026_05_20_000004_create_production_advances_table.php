<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla production_advances
        Schema::create('production_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')
                  ->constrained('productions')
                  ->onDelete('cascade');
            $table->string('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        // 2. Eliminar columna advance de productions (ya no es necesaria)
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('advance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_advances');

        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('advance', 12, 2)->default(0)->after('discount');
        });
    }
};
