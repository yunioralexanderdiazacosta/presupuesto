<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Corrección de datos: líneas de presupuesto cuya subfamilia (Level3) pertenece al Level1 de OTRA temporada
// (ej. cosechas de la temporada 9 apuntando al "Cosecha" de la temporada 4). El dashboard agrupa por level1_id,
// por lo que aparecían dos barras para el mismo Level1. Se repuntan al Level3 equivalente (mismo nombre de
// Level1 > Level2 > Level3) de la temporada de la línea. Los valores originales se guardan para poder revertir.
return new class extends Migration
{
    private const TABLES = [
        'administrations', 'fields', 'services', 'supplies',
        'agrochemicals', 'fertilizers', 'man_powers', 'harvests',
    ];

    private const BACKUP = 'subfamily_season_fix_backup';

    public function up(): void
    {
        Schema::create(self::BACKUP, function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('row_id');
            $table->unsignedBigInteger('old_subfamily_id');
            $table->unsignedBigInteger('new_subfamily_id');
        });

        foreach (self::TABLES as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'subfamily_id') || !Schema::hasColumn($tableName, 'season_id')) {
                continue;
            }

            $wrong = DB::table("$tableName as a")
                ->join('level3s as l3', 'a.subfamily_id', '=', 'l3.id')
                ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->whereColumn('a.season_id', '!=', 'l1.season_id')
                ->select('a.id', 'a.season_id', 'a.team_id', 'a.subfamily_id', 'l1.name as l1_name', 'l2.name as l2_name', 'l3.name as l3_name')
                ->get();

            foreach ($wrong as $row) {
                $target = DB::table('level3s as l3')
                    ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('l1.season_id', $row->season_id)
                    ->where('l1.team_id', $row->team_id)
                    ->where('l1.name', $row->l1_name)
                    ->where('l2.name', $row->l2_name)
                    ->where('l3.name', $row->l3_name)
                    ->value('l3.id');

                // Sin equivalente en la temporada de la línea no se toca el dato
                if (!$target) {
                    continue;
                }

                DB::table(self::BACKUP)->insert([
                    'table_name' => $tableName,
                    'row_id' => $row->id,
                    'old_subfamily_id' => $row->subfamily_id,
                    'new_subfamily_id' => $target,
                ]);

                DB::table($tableName)->where('id', $row->id)->update(['subfamily_id' => $target]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable(self::BACKUP)) {
            foreach (DB::table(self::BACKUP)->get() as $row) {
                DB::table($row->table_name)->where('id', $row->row_id)->update(['subfamily_id' => $row->old_subfamily_id]);
            }
        }

        Schema::dropIfExists(self::BACKUP);
    }
};
