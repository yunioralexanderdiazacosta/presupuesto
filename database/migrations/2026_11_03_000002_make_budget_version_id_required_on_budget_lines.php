<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $lineTables = [
        'agrochemicals',
        'fertilizers',
        'supplies',
        'services',
        'harvests',
        'man_powers',
        'administrations',
        'fields',
    ];

    public function up(): void
    {
        foreach ($this->lineTables as $table) {
            // Líneas creadas después de la primera migración (sin versión): van a la versión oficial de su temporada
            DB::statement("
                UPDATE {$table} t
                JOIN budget_versions v ON v.season_id = t.season_id AND v.is_official = 1
                SET t.budget_version_id = v.id
                WHERE t.budget_version_id IS NULL
            ");

            $remaining = DB::table($table)->whereNull('budget_version_id')->count();
            if ($remaining > 0) {
                throw new RuntimeException("La tabla {$table} tiene {$remaining} línea(s) sin temporada o sin versión oficial; corrija esos registros antes de migrar.");
            }

            DB::statement("ALTER TABLE {$table} MODIFY budget_version_id BIGINT UNSIGNED NOT NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->lineTables as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY budget_version_id BIGINT UNSIGNED NULL");
        }
    }
};
