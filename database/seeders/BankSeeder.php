<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desactivar foreign key checks temporalmente
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Bank::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $banks = [
            ['name' => 'Banco de Chile', 'code' => '001', 'active' => true],
            ['name' => 'Banco Santander Chile', 'code' => '037', 'active' => true],
            ['name' => 'Banco Estado', 'code' => '012', 'active' => true],
            ['name' => 'Scotiabank Chile', 'code' => '014', 'active' => true],
            ['name' => 'Banco BCI', 'code' => '016', 'active' => true],
            ['name' => 'Banco Itaú Chile', 'code' => '039', 'active' => true],
            ['name' => 'Banco Security', 'code' => '049', 'active' => true],
            ['name' => 'Banco Falabella', 'code' => '051', 'active' => true],
            ['name' => 'Banco Ripley', 'code' => '053', 'active' => true],
            ['name' => 'Banco Consorcio', 'code' => '055', 'active' => true],
            ['name' => 'Banco BICE', 'code' => '028', 'active' => true],
            ['name' => 'HSBC Bank Chile', 'code' => '031', 'active' => true],
            ['name' => 'Banco Internacional', 'code' => '009', 'active' => true],
            ['name' => 'Coopeuch', 'code' => '672', 'active' => true],
            ['name' => 'Banco Paris', 'code' => '054', 'active' => true],
        ];

        foreach ($banks as $bank) {
            Bank::create($bank);
        }
    }
}
