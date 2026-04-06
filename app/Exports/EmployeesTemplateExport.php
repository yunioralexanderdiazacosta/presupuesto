<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmployeesTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'rut',
            'primer_nombre',
            'segundo_nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'nacionalidad',
            'estado',
        ];
    }

    public function array(): array
    {
        return [
            [
                '12.345.678-5',
                'Juan',
                'Carlos',
                'Pérez',
                'González',
                '15/03/1990',
                'Chilena',
                'Activo',
            ],
            [
                '9.876.543-3',
                'María',
                '',
                'López',
                'Soto',
                '22/07/1985',
                'Chilena',
                'Activo',
            ],
        ];
    }
}
