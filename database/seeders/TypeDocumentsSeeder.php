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
            'name' => 'FACTURA'
        ]);

        TypeDocument::create([
            'name' => 'BOLETA'
        ]);

        TypeDocument::create([
            'name' => 'NOTA CREDITO'
        ]);

        TypeDocument::create([
            'name' => 'NOTA DEBITO'
        ]);
    }
}
