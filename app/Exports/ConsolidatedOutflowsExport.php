<?php

namespace App\Exports;

use App\Models\Outflow;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ConsolidatedOutflowsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $term;
    protected $month;
    protected $supplierId;
    protected $level2Id;
    protected $level3Id;
    protected $teamId;
    protected $seasonId;

    public function __construct($term = '', $month = '', $supplierId = '', $level2Id = '', $level3Id = '')
    {
        $this->term = $term;
        $this->month = $month;
        $this->supplierId = $supplierId;
        $this->level2Id = $level2Id;
        $this->level3Id = $level3Id;
        $this->teamId = Auth::user()->team_id;
        $this->seasonId = session('season_id');
    }

    public function collection()
    {
        $query = Outflow::with([
            'invoiceProduct:id,invoice_id,product_id,unit_price',
            'invoiceProduct.product:id,name,unit_id',
            'invoiceProduct.product.unit:id,name',
            'invoiceProduct.invoice:id,supplier_id,number_document,type_document_id',
            'invoiceProduct.invoice.supplier:id,name',
            'invoiceProduct.invoice.typeDocument:id,name',
            'creditDebitNoteItem:id,credit_debit_note_id,product_id,unit_price',
            'creditDebitNoteItem.product:id,name,unit_id',
            'creditDebitNoteItem.product.unit:id,name',
            'creditDebitNoteItem.creditDebitNote:id,supplier_id,number',
            'creditDebitNoteItem.creditDebitNote.supplier:id,name',
            'project:id,name',
            'operation:id,name',
            'machinery:id,cod_machinery,brand',
            'costCenters:id,outflow_id,cost_center_id,observations',
            'costCenters.costCenter:id,name,surface,development_state_id',
            'costCenters.costCenter.developmentState:id,name',
            'level3:id,name,level2_id',
            'level3.level2:id,name,level1_id',
            'level3.level2.level1:id,name',
        ])
        ->select(['id', 'invoice_product_id', 'credit_debit_note_item_id', 'project_id',
                   'operation_id', 'machinery_id', 'quantity', 'notes', 'date',
                   'team_id', 'season_id', 'level3_id'])
        ->where('team_id', $this->teamId)
        ->where('season_id', $this->seasonId);

        if ($this->month) {
            $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->month]);
        }

        if ($this->supplierId) {
            $query->where(function($q) {
                $q->whereHas('invoiceProduct.invoice.supplier', function($subQ) {
                    $subQ->where('suppliers.id', $this->supplierId);
                })->orWhereHas('creditDebitNoteItem.creditDebitNote.supplier', function($subQ) {
                    $subQ->where('suppliers.id', $this->supplierId);
                });
            });
        }

        if ($this->level2Id) {
            $query->whereHas('level3.level2', function($subQ) {
                $subQ->where('level2s.id', $this->level2Id);
            });
        }

        if ($this->level3Id) {
            $query->whereHas('level3', function($subQ) {
                $subQ->where('level3s.id', $this->level3Id);
            });
        }

        if ($this->term) {
            $term = $this->term;
            $query->where(function($q) use ($term) {
                $q->whereHas('invoiceProduct.product', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('creditDebitNoteItem.product', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('invoiceProduct.invoice.supplier', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('creditDebitNoteItem.creditDebitNote.supplier', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('project', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('costCenters.costCenter', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('level3', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('level3.level2', fn($s) => $s->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('level3.level2.level1', fn($s) => $s->where('name', 'like', "%{$term}%"));
            });
        }

        $outflows = $query->orderBy('id', 'desc')->get();

        // Expandir por centros de costo
        $rows = collect();

        foreach ($outflows as $outflow) {
            $isInvoice = !is_null($outflow->invoice_product_id);

            $common = [
                'outflow_id' => $outflow->id,
                'date' => $outflow->date ? \Carbon\Carbon::parse($outflow->date)->format('d-m-Y') : '',
                'month' => $outflow->date ? \Carbon\Carbon::parse($outflow->date)->locale('es')->translatedFormat('F') : '',
                'supplier' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->supplier->name ?? '-')
                    : ($outflow->creditDebitNoteItem->creditDebitNote->supplier->name ?? '-'),
                'number_document' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->number_document ?? '-')
                    : ($outflow->creditDebitNoteItem->creditDebitNote->number ?? '-'),
                'tipo_documento' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->typeDocument->name ?? 'Factura')
                    : 'Nota Débito',
                'product_name' => $isInvoice
                    ? ($outflow->invoiceProduct->product->name ?? '-')
                    : ($outflow->creditDebitNoteItem->product->name ?? '-'),
                'unit_name' => $isInvoice
                    ? ($outflow->invoiceProduct->product->unit->name ?? '-')
                    : ($outflow->creditDebitNoteItem->product->unit->name ?? '-'),
                'quantity_total' => $outflow->quantity,
                'unit_price' => $isInvoice
                    ? ($outflow->invoiceProduct->unit_price ?? 0)
                    : ($outflow->creditDebitNoteItem->unit_price ?? 0),
                'project' => $outflow->project->name ?? '',
                'operation' => $outflow->operation->name ?? '',
                'machinery' => $outflow->machinery ? trim($outflow->machinery->cod_machinery . ' - ' . $outflow->machinery->brand) : '',
                'notes' => $outflow->notes ?? '',
                'level1_name' => $outflow->level3->level2->level1->name ?? '',
                'level2_name' => $outflow->level3->level2->name ?? '',
                'level3_name' => $outflow->level3->name ?? '',
            ];

            $totalSuperficie = $outflow->costCenters->sum(fn($occ) => $occ->costCenter->surface ?? 0);
            $cantidadPorHa = $totalSuperficie > 0 ? $outflow->quantity / $totalSuperficie : 0;

            if ($outflow->costCenters->isEmpty()) {
                $rows->push(array_merge($common, [
                    'cost_center_name' => '-',
                    'surface' => 0,
                    'cantidad_asignada' => $outflow->quantity,
                    'development_state' => '',
                    'cantidad_por_ha' => 0,
                    'total' => $outflow->quantity * $common['unit_price'],
                ]));
                continue;
            }

            foreach ($outflow->costCenters as $occ) {
                $superficie = $occ->costCenter->surface ?? 0;
                $cantidadAsignada = $superficie == 0 ? $outflow->quantity : $superficie * $cantidadPorHa;
                $totalCalculado = $cantidadAsignada * $common['unit_price'];

                $rows->push(array_merge($common, [
                    'cost_center_name' => $occ->costCenter->name ?? '-',
                    'surface' => $superficie,
                    'cantidad_asignada' => round($cantidadAsignada, 2),
                    'development_state' => $occ->costCenter->developmentState->name ?? '',
                    'cantidad_por_ha' => round($cantidadPorHa, 4),
                    'total' => round($totalCalculado, 2),
                ]));
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID Salida', 'Fecha', 'Mes', 'Proveedor', 'N° Documento', 'Tipo Documento',
            'Producto', 'Unidad', 'Nivel 1', 'Nivel 2', 'Nivel 3',
            'Cantidad Total', 'Precio Unitario', 'Proyecto', 'Operación', 'Maquinaria',
            'Centro de Costo', 'Superficie CC (ha)', 'Cantidad Asignada',
            'Estado Desarrollo', 'Cantidad/Ha', 'Total', 'Notas',
        ];
    }

    public function map($row): array
    {
        return [
            $row['outflow_id'],
            $row['date'],
            $row['month'],
            $row['supplier'],
            $row['number_document'],
            $row['tipo_documento'],
            $row['product_name'],
            $row['unit_name'],
            $row['level1_name'],
            $row['level2_name'],
            $row['level3_name'],
            $row['quantity_total'],
            $row['unit_price'],
            $row['project'],
            $row['operation'],
            $row['machinery'],
            $row['cost_center_name'],
            $row['surface'],
            $row['cantidad_asignada'],
            $row['development_state'],
            $row['cantidad_por_ha'],
            $row['total'],
            $row['notes'],
        ];
    }
}
