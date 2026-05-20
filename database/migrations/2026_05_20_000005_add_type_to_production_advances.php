<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columna type a production_advances
        Schema::table('production_advances', function (Blueprint $table) {
            $table->string('type')->default('advance')->after('production_id');
            // type: 'advance' | 'discount'
        });

        // 2. Eliminar columna discount de productions (ahora va como type='discount' en production_advances)
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }

    public function down(): void
    {
        Schema::table('production_advances', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->default(0)->after('fruit_id');
        });
    }
};
