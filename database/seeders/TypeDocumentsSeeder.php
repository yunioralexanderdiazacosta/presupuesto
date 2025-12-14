<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TypeDocument;

class TypeDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeDocument::create([
            'name' => 'FACTURA',
            'code' => '33'
        ]);

        TypeDocument::create([
            'name' => 'BOLETA',
            'code' => '39'
        ]);

        TypeDocument::create([
            'name' => 'NOTA CREDITO',
            'code' => '61'
        ]);

        TypeDocument::create([
            'name' => 'NOTA DEBITO',
            'code' => '56'
        ]);
    }
}
