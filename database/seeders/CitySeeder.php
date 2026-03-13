<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'Arica', 'Iquique', 'Antofagasta', 'Copiapó', 'La Serena', 'Coquimbo',
            'Valparaíso', 'Viña del Mar', 'Quillota', 'San Felipe', 'Los Andes',
            'Santiago', 'Rancagua', 'San Fernando', 'Talca', 'Curicó', 'Linares',
            'Chillán', 'Concepción', 'Los Ángeles', 'Temuco', 'Valdivia',
            'Osorno', 'Puerto Montt', 'Coyhaique', 'Punta Arenas',
        ];

        foreach ($cities as $name) {
            City::firstOrCreate(['name' => $name]);
        }
    }
}
