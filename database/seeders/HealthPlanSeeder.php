<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HealthPlan;

class HealthPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = ['Fonasa', 'Banmédica', 'Colmena', 'Cruz Blanca', 'Consalud', 'Nueva Masvida', 'Vida Tres', 'Esencial'];

        foreach ($plans as $name) {
            HealthPlan::firstOrCreate(['name' => $name]);
        }
    }
}
