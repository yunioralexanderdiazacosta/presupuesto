<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SuppliersTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'nombre',
            'rut',
            'contacto',
            'email',
            'telefono',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Proveedor Ejemplo S.A.',
                '76.123.456-7',
                'Juan Pérez',
                'juan@ejemplo.cl',
                '+56912345678',
            ],
            [
                'Distribuidora Norte Ltda.',
                '12.345.678-9',
                'María González',
                '',
                '',
            ],
        ];
    }
}
