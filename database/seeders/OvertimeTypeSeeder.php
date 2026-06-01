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
                'hourly_rate_factor'  => 0.0055556,
                'overtime_multiplier' => 1.50,
                'description'         => 'Hora extra en día de semana. Factor 0.0055556 = 28/(30×168) para jornada 42hrs/semana (tarifa ordinaria pura). Recargo 50% aplicado por overtime_multiplier.',
                'active'              => true,
            ],
            [
                'name'                => 'Hora Extra Festivo',
                'hourly_rate_factor'  => 0.0055556,
                'overtime_multiplier' => 2.00,
                'description'         => 'Hora extra en día festivo o domingo. Mismo factor base, recargo 100%.',
                'active'              => true,
            ],
            [
                'name'                => 'Hora Extra Urgencia',
                'hourly_rate_factor'  => 0.0055556,
                'overtime_multiplier' => 3.00,
                'description'         => 'Hora extra por emergencia o urgencia operacional. Recargo 200%.',
                'active'              => true,
            ],
        ];

        foreach ($types as $type) {
            OvertimeType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
