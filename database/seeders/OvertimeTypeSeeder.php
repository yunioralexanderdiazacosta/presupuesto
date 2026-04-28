<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OvertimeType;

class OvertimeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'                => 'Hora Extra Normal',
                'hourly_rate_factor'  => 0.0079545,
                'overtime_multiplier' => 1.50,
                'description'         => 'Hora extra en día de semana. Factor 0.0079545 para jornada 44hrs/semana (sueldo ÷ 30 × 28 ÷ 176). Recargo 50%.',
                'active'              => true,
            ],
            [
                'name'                => 'Hora Extra Festivo',
                'hourly_rate_factor'  => 0.0079545,
                'overtime_multiplier' => 2.00,
                'description'         => 'Hora extra en día festivo o domingo. Mismo factor base, recargo 100%.',
                'active'              => true,
            ],
            [
                'name'                => 'Hora Extra Urgencia',
                'hourly_rate_factor'  => 0.0079545,
                'overtime_multiplier' => 3.00,
                'description'         => 'Hora extra por emergencia o urgencia operacional. Recargo 200%.',
                'active'              => true,
            ],
        ];

        foreach ($types as $type) {
            OvertimeType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
