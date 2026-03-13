<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Afp;

class AfpSeeder extends Seeder
{
    public function run(): void
    {
        $afps = ['Capital', 'Cuprum', 'Habitat', 'Modelo', 'PlanVital', 'ProVida', 'Uno'];

        foreach ($afps as $name) {
            Afp::firstOrCreate(['name' => $name]);
        }
    }
}
