<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\ExpenseReport;
use Illuminate\Support\Facades\Auth;

class ExpenseReportsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'Nº Rendición',
            'Estado',
            'Rendidor',
            'Aprobador',
            'Fecha Creación',
            'Fecha Item',
            'Proveedor',
            'Producto',
            'Descripción',
            'Monto',
            'Contabilizado',
            'Nº Factura',
            'Notas',
        ];
    }

    public function collection()
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $reports = ExpenseReport::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->with([
                'user:id,name',
                'assignedTo:id,name',
                'items.supplier:id,name',
                'items.invoice:id,number_document',
            ])
            ->orderByDesc('created_at')
            ->get();

        $rows = collect();

        foreach ($reports as $report) {
            if ($report->items->isEmpty()) {
                // Rendición sin items - mostrar solo encabezado
                $rows->push([
                    'numero' => $report->number,
                    'estado' => $report->status_label,
                    'rendidor' => $report->user->name ?? '',
                    'aprobador' => $report->assignedTo->name ?? '',
                    'fecha_creacion' => $report->created_at->format('d/m/Y'),
                    'fecha_item' => '',
                    'proveedor' => '',
                    'producto' => '',
                    'descripcion' => $report->description ?? '',
                    'monto' => 0,
                    'contabilizado' => '',
                    'nro_factura' => '',
                    'notas' => '',
                ]);
            } else {
                foreach ($report->items as $item) {
                    $rows->push([
                        'numero' => $report->number,
                        'estado' => $report->status_label,
                        'rendidor' => $report->user->name ?? '',
                        'aprobador' => $report->assignedTo->name ?? '',
                        'fecha_creacion' => $report->created_at->format('d/m/Y'),
                        'fecha_item' => $item->date->format('d/m/Y'),
                        'proveedor' => $item->supplier->name ?? '',
                        'producto' => $item->product_name ?? '',
                        'descripcion' => $item->description ?? '',
                        'monto' => (float) $item->amount,
                        'contabilizado' => $item->is_contabilized ? 'Sí' : 'No',
                        'nro_factura' => $item->invoice->number_document ?? '',
                        'notas' => $item->notes ?? '',
                    ]);
                }
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
