<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CausalesTerminoSeeder extends Seeder
{
    public function run(): void
    {
        $causales = [
            [
                'codigo'        => '159-1',
                'nombre'        => 'Mutuo Acuerdo',
                'articulo'      => 'Art. 159 N°1',
                'aplica_faena'  => true,
                'activa'        => true,
                'orden'         => 1,
            ],
            [
                'codigo'        => '159-2',
                'nombre'        => 'Renuncia Voluntaria',
                'articulo'      => 'Art. 159 N°2',
                'aplica_faena'  => true,
                'activa'        => true,
                'orden'         => 2,
            ],
            [
                'codigo'        => '159-4',
                'nombre'        => 'Vencimiento del Plazo',
                'articulo'      => 'Art. 159 N°4',
                'aplica_faena'  => true,
                'activa'        => true,
                'orden'         => 3,
            ],
            [
                'codigo'        => '159-5',
                'nombre'        => 'Conclusión del Trabajo o Servicio que dio Origen al Contrato',
                'articulo'      => 'Art. 159 N°5',
                'aplica_faena'  => true,
                'activa'        => true,
                'orden'         => 4,
            ],
            [
                'codigo'        => '160',
                'nombre'        => 'Despido por Falta de Probidad u otras Causales Graves',
                'articulo'      => 'Art. 160',
                'aplica_faena'  => true,
                'activa'        => true,
                'orden'         => 5,
            ],
            [
                'codigo'        => '161',
                'nombre'        => 'Necesidades de la Empresa',
                'articulo'      => 'Art. 161',
                'aplica_faena'  => true,
                'activa'        => true,
                'orden'         => 6,
            ],
        ];

        DB::table('causales_termino')->insert($causales);
    }
}
