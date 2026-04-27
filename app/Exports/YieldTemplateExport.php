<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class YieldTemplateExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $contracts;
    protected $date;
    protected $parcelName;
    protected $teamName;

    // Número de filas de encabezado antes de los datos
    const HEADER_ROWS = 4; // fila info, fila header1, fila header2, fila header3 (subheaders)

    public function __construct($contracts, $date, $parcelName, $teamName)
    {
        $this->contracts  = $contracts;
        $this->date       = $date;
        $this->parcelName = $parcelName;
        $this->teamName   = $teamName;
    }

    public function title(): string
    {
        return 'Plantilla Tarjas';
    }

    public function array(): array
    {
        $rows = [];
        $perPage = 30;
        $total   = $this->contracts->count();
        $filled  = max($total, $perPage);

        foreach ($this->contracts->values() as $i => $contract) {
            $rows[] = [
                $i + 1,
                $contract->employee->paternal_surname . ' ' . $contract->employee->maternal_surname . ', ' . $contract->employee->first_name,
                $contract->employee->rut,
                '', '', '', '',
                '', '', '', '',
                '', '',
            ];
        }

        // Rellenar filas vacías hasta completar $perPage
        for ($i = $total; $i < $perPage; $i++) {
            $rows[] = [
                $i + 1,
                '', '', '', '', '', '',
                '', '', '', '',
                '', '',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Estilos básicos se aplican vía events para mayor control
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalDataRows = 30;
                $lastRow = self::HEADER_ROWS + $totalDataRows;

                // ── Fila 1: Título ──────────────────────────────────────────
                $sheet->insertNewRowBefore(1, self::HEADER_ROWS);

                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'PLANTILLA DE TARJAS');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Fila 2: Info (empresa, fecha, parcela) ──────────────────
                $sheet->mergeCells('A2:M2');
                $info = $this->teamName . '   |   Fecha: ' . $this->date->format('d/m/Y')
                    . '   |   Día: ' . ucfirst($this->date->locale('es')->isoFormat('dddd'))
                    . '   |   Parcela: ' . $this->parcelName;
                $sheet->setCellValue('A2', $info);
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Fila 3: Encabezado de grupos (Labor 1 / Labor 2) ────────
                // A3 = #, B3 = Nombre, C3 = RUT, D3:G3 = Labor 1, H3:K3 = Labor 2, L3 = Obs
                $sheet->mergeCells('A3:A4');
                $sheet->setCellValue('A3', '#');
                $sheet->mergeCells('B3:B4');
                $sheet->setCellValue('B3', 'Nombre');
                $sheet->mergeCells('C3:C4');
                $sheet->setCellValue('C3', 'RUT');
                $sheet->mergeCells('D3:G3');
                $sheet->setCellValue('D3', 'Labor 1');
                $sheet->mergeCells('H3:K3');
                $sheet->setCellValue('H3', 'Labor 2');
                $sheet->mergeCells('L3:L4');
                $sheet->setCellValue('L3', 'Precip. Obj.');
                $sheet->mergeCells('M3:M4');
                $sheet->setCellValue('M3', 'Obs.');

                // Estilo fila 3
                $sheet->getStyle('A3:M3')->applyFromArray([
                    'font'      => ['bold' => true],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('D3:G3')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8D8E8']],
                ]);
                $sheet->getStyle('H3:K3')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D8E8C8']],
                ]);

                // ── Fila 4: Sub-encabezados ──────────────────────────────────
                $sheet->setCellValue('D4', 'Labor');
                $sheet->setCellValue('E4', 'Trato');
                $sheet->setCellValue('F4', 'Cant.');
                $sheet->setCellValue('G4', 'Hrs.');
                $sheet->setCellValue('H4', 'Labor');
                $sheet->setCellValue('I4', 'Trato');
                $sheet->setCellValue('J4', 'Cant.');
                $sheet->setCellValue('K4', 'Hrs.');

                $sheet->getStyle('A4:M4')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 8],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('D4:G4')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8D8E8']],
                ]);
                $sheet->getStyle('H4:K4')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D8E8C8']],
                ]);

                // ── Alturas de filas ─────────────────────────────────────────
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(16);
                $sheet->getRowDimension(3)->setRowHeight(16);
                $sheet->getRowDimension(4)->setRowHeight(14);

                // Altura filas de datos
                for ($r = 5; $r <= $lastRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(18);
                }

                // ── Bordes en toda la tabla ──────────────────────────────────
                $sheet->getStyle('A3:M' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '333333'],
                        ],
                    ],
                ]);

                // ── Anchos de columna ────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(8);
                $sheet->getColumnDimension('G')->setWidth(8);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(14);
                $sheet->getColumnDimension('J')->setWidth(8);
                $sheet->getColumnDimension('K')->setWidth(8);
                $sheet->getColumnDimension('L')->setWidth(12);
                $sheet->getColumnDimension('M')->setWidth(20);

                // Centrar columnas numéricas en datos
                $sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C5:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F5:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J5:K' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('L5:L' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ── Línea de firma al final ──────────────────────────────────
                $signRow = $lastRow + 3;
                $sheet->setCellValue('B' . $signRow, 'Supervisor: ___________________________');
                $sheet->getStyle('B' . $signRow)->applyFromArray([
                    'font' => ['size' => 9],
                ]);
            },
        ];
    }
}
