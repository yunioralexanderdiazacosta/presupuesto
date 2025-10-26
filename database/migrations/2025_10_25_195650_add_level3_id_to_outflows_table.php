<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna level3_id
        Schema::table('outflows', function (Blueprint $table) {
            $table->foreignId('level3_id')->nullable()->after('season_id')->constrained('level3s')->onDelete('set null');
        });

        // 2. Migrar datos: copiar level3_id de products a outflows
        DB::statement('
            UPDATE outflows o
            INNER JOIN invoice_product ip ON o.invoice_product_id = ip.id
            INNER JOIN products p ON ip.product_id = p.id
            SET o.level3_id = p.level3_id
            WHERE p.level3_id IS NOT NULL
        ');

        echo "\n✅ Migración completada: level3_id copiado de products a outflows\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outflows', function (Blueprint $table) {
            $table->dropForeign(['level3_id']);
            $table->dropColumn('level3_id');
        });
    }
};
