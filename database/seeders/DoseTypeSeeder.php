<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DoseType;

class DoseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DoseType::create([
            'name' => 'Hectarea'
        ]);

        DoseType::create([
            'name' => 'Hectolitro'
        ]);
    }
}
