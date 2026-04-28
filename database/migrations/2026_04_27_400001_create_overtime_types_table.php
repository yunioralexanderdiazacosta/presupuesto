<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('hourly_rate_factor', 10, 7)->default(0.0079545)->comment('Factor para convertir sueldo mensual a valor hora ordinaria (44hrs/semana)');
            $table->decimal('overtime_multiplier', 5, 2)->default(1.50)->comment('Recargo sobre hora ordinaria (1.5 = 50% extra)');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_types');
    }
};
