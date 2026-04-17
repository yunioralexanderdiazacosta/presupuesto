<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Renombrar columna hours → workdays y cambiar precisión en un solo paso
        // Usar CHANGE COLUMN para compatibilidad con MySQL 5.x
        DB::statement('ALTER TABLE `daily_yields` CHANGE `hours` `workdays` DECIMAL(3,2) NOT NULL DEFAULT 0');

        // 3. Convertir datos existentes: horas → fracción de jornada
        $dayFields = [
            1 => 'monday_hours',
            2 => 'tuesday_hours',
            3 => 'wednesday_hours',
            4 => 'thursday_hours',
            5 => 'friday_hours',
            6 => 'saturday_hours',
            7 => 'sunday_hours',
        ];

        $schedules = DB::table('work_schedules')->get()->keyBy(function ($s) {
            return $s->team_id . '-' . $s->season_id;
        });

        DB::table('daily_yields')->orderBy('id')->chunk(500, function ($yields) use ($schedules, $dayFields) {
            foreach ($yields as $yield) {
                $key = $yield->team_id . '-' . $yield->season_id;
                $schedule = $schedules->get($key);
                $dayOfWeek = Carbon::parse($yield->date)->dayOfWeekIso;
                $field = $dayFields[$dayOfWeek] ?? 'monday_hours';
                $maxHours = $schedule ? (float) $schedule->{$field} : 8.0;

                $workdays = ($maxHours > 0)
                    ? round((float) $yield->workdays / $maxHours, 2)
                    : 0;

                DB::table('daily_yields')
                    ->where('id', $yield->id)
                    ->update(['workdays' => $workdays]);
            }
        });
    }

    public function down(): void
    {
        // Revertir: jornadas → horas (multiplicar por horas del schedule)
        $dayFields = [
            1 => 'monday_hours',
            2 => 'tuesday_hours',
            3 => 'wednesday_hours',
            4 => 'thursday_hours',
            5 => 'friday_hours',
            6 => 'saturday_hours',
            7 => 'sunday_hours',
        ];

        $schedules = DB::table('work_schedules')->get()->keyBy(function ($s) {
            return $s->team_id . '-' . $s->season_id;
        });

        DB::table('daily_yields')->orderBy('id')->chunk(500, function ($yields) use ($schedules, $dayFields) {
            foreach ($yields as $yield) {
                $key = $yield->team_id . '-' . $yield->season_id;
                $schedule = $schedules->get($key);
                $dayOfWeek = Carbon::parse($yield->date)->dayOfWeekIso;
                $field = $dayFields[$dayOfWeek] ?? 'monday_hours';
                $maxHours = $schedule ? (float) $schedule->{$field} : 8.0;

                $hours = round((float) $yield->workdays * $maxHours, 1);

                DB::table('daily_yields')
                    ->where('id', $yield->id)
                    ->update(['workdays' => $hours]);
            }
        });

        // Renombrar de vuelta con CHANGE COLUMN para compatibilidad MySQL 5.x
        DB::statement('ALTER TABLE `daily_yields` CHANGE `workdays` `hours` DECIMAL(4,1) NOT NULL DEFAULT 0');
    }
};
