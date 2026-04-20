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
        $documents = [
            ['name' => 'FACTURA',      'code' => '33'],
            ['name' => 'BOLETA',       'code' => '39'],
            ['name' => 'NOTA CREDITO', 'code' => '61'],
            ['name' => 'NOTA DEBITO',  'code' => '56'],
        ];

        foreach ($documents as $doc) {
            TypeDocument::firstOrCreate(['code' => $doc['code']], $doc);
        }
    }
}
