<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountType;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Cuenta Corriente'],
            ['name' => 'Cuenta Vista'],
            ['name' => 'Cuenta de Ahorro'],
            ['name' => 'Chequera Electrónica'],
            ['name' => 'Cuenta RUT'],
        ];

        foreach ($types as $type) {
            AccountType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
