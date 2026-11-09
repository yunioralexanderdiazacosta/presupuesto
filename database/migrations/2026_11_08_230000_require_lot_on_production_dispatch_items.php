<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Un proceso siempre pertenece a un lote: las clasificaciones no pueden quedar sin lote.
     */
    public function up(): void
    {
        DB::table('production_dispatch_items')->whereNull('production_lot_id')->delete();

        DB::statement('ALTER TABLE production_dispatch_items MODIFY production_lot_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE production_dispatch_items MODIFY production_lot_id BIGINT UNSIGNED NULL');
    }
};
