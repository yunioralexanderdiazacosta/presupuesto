<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CostCentersTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /**
     * Headings for the Excel template
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'nombre_de_cc',
            'superficie',
            'observaciones',
            'frutal',
            'variedad',
            'parcela',
            'estado_de_desarrollo',
            'ano_de_plantacion',
            'razon_social',
        ];
    }

    /**
     * Sample rows or placeholders
     *
     * @return array
     */
    public function array(): array
    {
        return [
            [
                'CC Norte',
                '10',
                'Observación 1',
                'Manzano',
                'Fuji',
                'Parcela 1',
                'Productivo',
                '2015',
                'Agro S.A.',
            ],
            [
                'CC Sur',
                '20',
                'Observación 2',
                'Peral',
                'Williams',
                'Parcela 2',
                'En desarrollo',
                '2018',
                'Campo Verde',
            ],
        ];
    }
}
