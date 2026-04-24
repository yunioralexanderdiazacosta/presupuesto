<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Feriados nacionales de Chile (team_id = NULL).
     * Fuente: Ley 19.973 y modificaciones. Válido para 2024, 2025 y 2026.
     *
     * Regla de traslado (Ley 19.668): feriados que caen martes-jueves
     * pueden trasladarse al lunes anterior (feriados "trasladables").
     * Los que se listan aquí ya tienen la fecha trasladada aplicada.
     */
    public function run(): void
    {
        $holidays = [

            // ── 2024 ──────────────────────────────────────────────────────────
            ['date' => '2024-01-01', 'name' => 'Año Nuevo',                          'is_recurring' => false],
            ['date' => '2024-03-29', 'name' => 'Viernes Santo',                      'is_recurring' => false],
            ['date' => '2024-03-30', 'name' => 'Sábado Santo',                       'is_recurring' => false],
            ['date' => '2024-05-01', 'name' => 'Día del Trabajo',                    'is_recurring' => false],
            ['date' => '2024-05-21', 'name' => 'Glorias Navales',                    'is_recurring' => false],
            ['date' => '2024-06-20', 'name' => 'Día de los Pueblos Indígenas',       'is_recurring' => false],
            ['date' => '2024-06-29', 'name' => 'San Pedro y San Pablo',              'is_recurring' => false],
            ['date' => '2024-07-16', 'name' => 'Virgen del Carmen',                  'is_recurring' => false],
            ['date' => '2024-08-15', 'name' => 'Asunción de la Virgen',              'is_recurring' => false],
            ['date' => '2024-09-18', 'name' => 'Fiestas Patrias',                    'is_recurring' => false],
            ['date' => '2024-09-19', 'name' => 'Día de las Glorias del Ejército',   'is_recurring' => false],
            ['date' => '2024-09-20', 'name' => 'Feriado Adicional Fiestas Patrias', 'is_recurring' => false],
            ['date' => '2024-10-12', 'name' => 'Encuentro de Dos Mundos',            'is_recurring' => false],
            ['date' => '2024-10-25', 'name' => 'Día de las Iglesias Evangélicas',   'is_recurring' => false],
            ['date' => '2024-11-01', 'name' => 'Día de Todos los Santos',            'is_recurring' => false],
            ['date' => '2024-12-08', 'name' => 'Inmaculada Concepción',              'is_recurring' => false],
            ['date' => '2024-12-25', 'name' => 'Navidad',                            'is_recurring' => false],
            ['date' => '2024-12-31', 'name' => 'Feriado Bancario / Fin de Año',     'is_recurring' => false],

            // ── 2025 ──────────────────────────────────────────────────────────
            ['date' => '2025-01-01', 'name' => 'Año Nuevo',                          'is_recurring' => false],
            ['date' => '2025-04-18', 'name' => 'Viernes Santo',                      'is_recurring' => false],
            ['date' => '2025-04-19', 'name' => 'Sábado Santo',                       'is_recurring' => false],
            ['date' => '2025-05-01', 'name' => 'Día del Trabajo',                    'is_recurring' => false],
            ['date' => '2025-05-21', 'name' => 'Glorias Navales',                    'is_recurring' => false],
            ['date' => '2025-06-20', 'name' => 'Día de los Pueblos Indígenas',       'is_recurring' => false],
            ['date' => '2025-06-30', 'name' => 'San Pedro y San Pablo (traslado)',   'is_recurring' => false], // 29/6 = domingo
            ['date' => '2025-07-16', 'name' => 'Virgen del Carmen',                  'is_recurring' => false],
            ['date' => '2025-08-15', 'name' => 'Asunción de la Virgen',              'is_recurring' => false],
            ['date' => '2025-09-18', 'name' => 'Fiestas Patrias',                    'is_recurring' => false],
            ['date' => '2025-09-19', 'name' => 'Día de las Glorias del Ejército',   'is_recurring' => false],
            ['date' => '2025-10-13', 'name' => 'Encuentro de Dos Mundos (traslado)', 'is_recurring' => false], // 12/10 = domingo
            ['date' => '2025-10-31', 'name' => 'Día de las Iglesias Evangélicas',   'is_recurring' => false],
            ['date' => '2025-11-01', 'name' => 'Día de Todos los Santos',            'is_recurring' => false],
            ['date' => '2025-12-08', 'name' => 'Inmaculada Concepción',              'is_recurring' => false],
            ['date' => '2025-12-25', 'name' => 'Navidad',                            'is_recurring' => false],

            // ── 2026 ──────────────────────────────────────────────────────────
            ['date' => '2026-01-01', 'name' => 'Año Nuevo',                          'is_recurring' => false],
            ['date' => '2026-04-03', 'name' => 'Viernes Santo',                      'is_recurring' => false],
            ['date' => '2026-04-04', 'name' => 'Sábado Santo',                       'is_recurring' => false],
            ['date' => '2026-05-01', 'name' => 'Día del Trabajo',                    'is_recurring' => false],
            ['date' => '2026-05-21', 'name' => 'Glorias Navales',                    'is_recurring' => false],
            ['date' => '2026-06-21', 'name' => 'Día de los Pueblos Indígenas',       'is_recurring' => false],
            ['date' => '2026-06-29', 'name' => 'San Pedro y San Pablo',              'is_recurring' => false], // 29/6 = lunes
            ['date' => '2026-07-16', 'name' => 'Virgen del Carmen',                  'is_recurring' => false],
            ['date' => '2026-08-15', 'name' => 'Asunción de la Virgen',              'is_recurring' => false],
            ['date' => '2026-09-18', 'name' => 'Fiestas Patrias',                    'is_recurring' => false],
            ['date' => '2026-09-19', 'name' => 'Día de las Glorias del Ejército',   'is_recurring' => false],
            ['date' => '2026-10-12', 'name' => 'Encuentro de Dos Mundos',            'is_recurring' => false], // 12/10 = lunes
            ['date' => '2026-10-30', 'name' => 'Día de las Iglesias Evangélicas',   'is_recurring' => false],
            ['date' => '2026-11-01', 'name' => 'Día de Todos los Santos',            'is_recurring' => false],
            ['date' => '2026-12-08', 'name' => 'Inmaculada Concepción',              'is_recurring' => false],
            ['date' => '2026-12-25', 'name' => 'Navidad',                            'is_recurring' => false],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date'], 'team_id' => null],
                ['name' => $holiday['name'], 'is_recurring' => $holiday['is_recurring']]
            );
        }
    }
}
