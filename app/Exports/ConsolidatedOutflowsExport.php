<?php

namespace App\Exports;

use App\Models\Outflow;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    protected $tipoGasto;
    protected $teamId;
    protected $seasonId;

    public function __construct($term = '', $month = '', $supplierId = '', $level2Id = '', $level3Id = '', $tipoGasto = '')
    {
        $this->term = $term;
        $this->month = $month;
        $this->supplierId = $supplierId;
        $this->level2Id = $level2Id;
        $this->level3Id = $level3Id;
        $this->tipoGasto = $tipoGasto;
        $this->teamId = Auth::user()->team_id;
        $this->seasonId = session('season_id');
    }

    public function collection()
    {
        $query = Outflow::with([
            'invoiceProduct:id,invoice_id,product_id,unit_price,branch_id',
            'invoiceProduct.product:id,name,unit_id',
            'invoiceProduct.product.unit:id,name',
            'invoiceProduct.branch:id,name',
            'invoiceProduct.invoice:id,supplier_id,number_document,type_document_id,company_reason_id',
            'invoiceProduct.invoice.supplier:id,name',
            'invoiceProduct.invoice.typeDocument:id,name',
            'invoiceProduct.invoice.companyReason:id,name',
            'creditDebitNoteItem:id,credit_debit_note_id,product_id,unit_price,branch_id',
            'creditDebitNoteItem.product:id,name,unit_id',
            'creditDebitNoteItem.product.unit:id,name',
            'creditDebitNoteItem.branch:id,name',
            'creditDebitNoteItem.creditDebitNote:id,supplier_id,number,invoice_id',
            'creditDebitNoteItem.creditDebitNote.supplier:id,name',
            'creditDebitNoteItem.creditDebitNote.invoice:id,company_reason_id',
            'creditDebitNoteItem.creditDebitNote.invoice.companyReason:id,name',
            'project:id,name',
            'operation:id,name',
            'machinery:id,cod_machinery,brand',
            'costCenters:id,outflow_id,cost_center_id,observations',
            'costCenters.costCenter:id,name,surface,development_state_id,branch_id,company_reason_id',
            'costCenters.costCenter.developmentState:id,name',
            'costCenters.costCenter.branch:id,name',
            'costCenters.costCenter.companyReason:id,name',
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

        $outflows = ($this->tipoGasto !== 'remuneraciones') ? $query->orderBy('id', 'desc')->get() : collect();

        // Expandir por centros de costo
        $rows = collect();

        foreach ($outflows as $outflow) {
            $isInvoice = !is_null($outflow->invoice_product_id);

            $common = [
                'outflow_id' => $outflow->id,
                'tipo_gasto' => 'Gestión',
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
                'branch_factura' => $isInvoice
                    ? ($outflow->invoiceProduct->branch->name ?? '-')
                    : ($outflow->creditDebitNoteItem->branch->name ?? '-'),
                'company_reason_factura' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->companyReason->name ?? '-')
                    : ($outflow->creditDebitNoteItem->creditDebitNote->invoice->companyReason->name ?? '-'),
            ];

            $totalSuperficie = $outflow->costCenters->sum(fn($occ) => $occ->costCenter->surface ?? 0);
            $cantidadPorHa = $totalSuperficie > 0 ? $outflow->quantity / $totalSuperficie : 0;

            if ($outflow->costCenters->isEmpty()) {
                $rows->push(array_merge($common, [
                    'cost_center_name' => '-',
                    'branch_cc' => '-',
                    'company_reason_cc' => '-',
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
                    'branch_cc' => $occ->costCenter->branch->name ?? '-',
                    'company_reason_cc' => $occ->costCenter->companyReason->name ?? '-',
                    'surface' => $superficie,
                    'cantidad_asignada' => round($cantidadAsignada, 2),
                    'development_state' => $occ->costCenter->developmentState->name ?? '',
                    'cantidad_por_ha' => round($cantidadPorHa, 4),
                    'total' => round($totalCalculado, 2),
                ]));
            }
        }

        // Remuneraciones (Labores por Centro de Costo): mismo criterio que el controlador.
        if ($this->tipoGasto !== 'gestion' && !$this->supplierId) {
            $remuneracionesRows = $this->buildRemuneracionesRows($this->teamId, $this->seasonId);

            if ($this->month) {
                $monthNum = (int) substr($this->month, 5, 2);
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, fn($r) => $r['month_id'] === $monthNum));
            }
            if ($this->level2Id) {
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, fn($r) => (string) $r['level2_id'] === (string) $this->level2Id));
            }
            if ($this->level3Id) {
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, fn($r) => (string) $r['level3_id'] === (string) $this->level3Id));
            }
            if ($this->term) {
                $termLower = mb_strtolower($this->term);
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, function($r) use ($termLower) {
                    $haystack = mb_strtolower(($r['product_name'] ?? '') . ' ' . ($r['level1_name'] ?? '') . ' ' . ($r['level2_name'] ?? '') . ' ' . ($r['level3_name'] ?? '') . ' ' . ($r['cost_center_name'] ?? ''));
                    return str_contains($haystack, $termLower);
                }));
            }

            foreach ($remuneracionesRows as $r) {
                $rows->push($r);
            }
        }

        return $rows;
    }

    /**
     * Construye las filas de "Remuneraciones" (jornadas, bonos mensuales y horas extra)
     * agrupadas por Centro de Costo, con la misma forma que las filas de Outflows (Gestión).
     * Siempre trae la temporada COMPLETA; el filtro de mes se aplica después en PHP.
     */
    private function buildRemuneracionesRows($teamId, $seasonId): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        $yields = DB::table('daily_yields as dy')
            ->join('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('dy.team_id', $teamId)
            ->where('dy.season_id', $seasonId)
            ->select(
                'dy.id',
                DB::raw('MONTH(dy.date) as month_id'),
                'dy.amount', 'dy.bonus_amount', 'dy.target_price_bonus', 'dy.workdays',
                'l3.id as level3_id', 'l3.name as level3_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'l1.name as level1_name'
            )
            ->get();

        $yieldIds = $yields->pluck('id');
        $yieldCCs = $yieldIds->isEmpty() ? collect() : DB::table('daily_yield_cost_center as dycc')
            ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->whereIn('dycc.daily_yield_id', $yieldIds)
            ->select('dycc.daily_yield_id', 'cc.id as cost_center_id', 'cc.name as cc_name', 'cc.surface',
                DB::raw("COALESCE(br.name, '-') as branch_name"), DB::raw("COALESCE(cr.name, '-') as company_reason_name"))
            ->get()
            ->groupBy('daily_yield_id');

        $bonuses = DB::table('monthly_bonuses as mb')
            ->join('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('mb.team_id', $teamId)
            ->where('mb.season_id', $seasonId)
            ->whereIn('mb.contract_id', $contractIds)
            ->select(
                'mb.id', 'mb.month_id', 'mb.amount',
                'l3.id as level3_id', 'l3.name as level3_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'l1.name as level1_name'
            )
            ->get();

        $bonusIds = $bonuses->pluck('id');
        $bonusCCs = $bonusIds->isEmpty() ? collect() : DB::table('monthly_bonus_cost_centers as mbcc')
            ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->whereIn('mbcc.monthly_bonus_id', $bonusIds)
            ->select('mbcc.monthly_bonus_id', 'cc.id as cost_center_id', 'cc.name as cc_name', 'cc.surface',
                DB::raw("COALESCE(br.name, '-') as branch_name"), DB::raw("COALESCE(cr.name, '-') as company_reason_name"))
            ->get()
            ->groupBy('monthly_bonus_id');

        $overtimes = DB::table('overtime_hours as oh')
            ->join('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('oh.team_id', $teamId)
            ->where('oh.season_id', $seasonId)
            ->whereIn('oh.contract_id', $contractIds)
            ->select(
                'oh.id', 'oh.month_id',
                DB::raw('ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot) as amount'),
                'l3.id as level3_id', 'l3.name as level3_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'l1.name as level1_name'
            )
            ->get();

        $overtimeIds = $overtimes->pluck('id');
        $overtimeCCs = $overtimeIds->isEmpty() ? collect() : DB::table('overtime_hour_cost_centers as ohcc')
            ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->whereIn('ohcc.overtime_hour_id', $overtimeIds)
            ->select('ohcc.overtime_hour_id', 'cc.id as cost_center_id', 'cc.name as cc_name', 'cc.surface',
                DB::raw("COALESCE(br.name, '-') as branch_name"), DB::raw("COALESCE(cr.name, '-') as company_reason_name"))
            ->get()
            ->groupBy('overtime_hour_id');

        // Agregamos por (mes + centro de costo + labor) en vez de emitir una fila por cada
        // registro crudo: una tarja puede repartirse entre decenas de CC, lo que generaba
        // decenas de miles de filas y agotaba la memoria disponible.
        $bucket = [];

        $addToBucket = function ($monthId, $ccId, $ccName, $branchName, $crName, $surface, $level1, $level2Name, $level2Id, $level3Name, $level3Id, $workdays, $amount) use (&$bucket) {
            $key = $monthId . '|' . ($ccId ?: 0) . '|' . ($level3Id ?: 0);
            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'month_id' => $monthId,
                    'cost_center_name' => $ccName,
                    'branch_cc' => $branchName,
                    'company_reason_cc' => $crName,
                    'surface' => $surface,
                    'level1_name' => $level1,
                    'level2_name' => $level2Name,
                    'level2_id' => $level2Id,
                    'level3_name' => $level3Name,
                    'level3_id' => $level3Id,
                    'workdays' => 0.0,
                    'amount' => 0.0,
                ];
            }
            $bucket[$key]['workdays'] += $workdays;
            $bucket[$key]['amount'] += $amount;
        };

        $processRecord = function ($record, $ccGrouped, string $source) use (&$addToBucket) {
            $monthId = (int) $record->month_id;
            $level1 = $record->level1_name ?? null;
            $level2Name = $record->level2_name ?? null;
            $level2Id = $record->level2_id ?? null;
            $level3Name = $record->level3_name ?? null;
            $level3Id = $record->level3_id ?? null;
            $workdays = (float) ($record->workdays ?? 0);
            $amount = $source === 'yield'
                ? (float) (($record->amount ?? 0) + ($record->bonus_amount ?? 0) + ($record->target_price_bonus ?? 0))
                : (float) ($record->amount ?? 0);

            $ccs = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');
            $nCCs = count($ccs);

            if ($nCCs === 0) {
                $addToBucket($monthId, 0, '-', '-', '-', 0, $level1, $level2Name, $level2Id, $level3Name, $level3Id, $workdays, $amount);
                return;
            }

            foreach ($ccs as $cc) {
                $surf = (float) ($cc->surface ?? 0);
                $prop = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;
                $addToBucket($monthId, $cc->cost_center_id, $cc->cc_name ?? '-', $cc->branch_name ?? '-', $cc->company_reason_name ?? '-', $surf, $level1, $level2Name, $level2Id, $level3Name, $level3Id, $workdays * $prop, $amount * $prop);
            }
        };

        foreach ($yields as $y) { $processRecord($y, $yieldCCs, 'yield'); }
        foreach ($bonuses as $b) { $processRecord($b, $bonusCCs, 'bonus'); }
        foreach ($overtimes as $o) { $processRecord($o, $overtimeCCs, 'overtime'); }

        $rows = [];
        foreach ($bucket as $key => $b) {
            $monthName = \Carbon\Carbon::createFromDate(2000, max(1, min(12, $b['month_id'] ?: 1)), 1)->locale('es')->translatedFormat('F');
            $rows[] = [
                'outflow_id' => 'R-' . $key,
                'tipo_gasto' => 'Remuneraciones',
                'date' => '',
                'month' => $monthName,
                'month_id' => $b['month_id'],
                'supplier' => '-',
                'number_document' => '-',
                'tipo_documento' => '-',
                'product_name' => $b['level3_name'] ?? 'Sin Labor',
                'unit_name' => 'Jornadas',
                'quantity_total' => round($b['workdays'], 2),
                'unit_price' => 0,
                'project' => '',
                'operation' => '',
                'machinery' => '',
                'notes' => '',
                'level1_name' => $b['level1_name'],
                'level2_name' => $b['level2_name'],
                'level2_id' => $b['level2_id'],
                'level3_name' => $b['level3_name'],
                'level3_id' => $b['level3_id'],
                'branch_factura' => '-',
                'company_reason_factura' => '-',
                'cost_center_name' => $b['cost_center_name'],
                'branch_cc' => $b['branch_cc'],
                'company_reason_cc' => $b['company_reason_cc'],
                'surface' => $b['surface'],
                'cantidad_asignada' => round($b['workdays'], 2),
                'development_state' => '',
                'cantidad_por_ha' => 0,
                'total' => round($b['amount'], 2),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID Salida', 'Tipo de Gasto', 'Fecha', 'Mes', 'Proveedor', 'Sucursal Factura', 'Razón Social Factura',
            'N° Documento', 'Tipo Documento',
            'Producto', 'Unidad', 'Nivel 1', 'Nivel 2', 'Nivel 3',
            'Cantidad Total', 'Precio Unitario', 'Proyecto', 'Operación', 'Maquinaria',
            'Centro de Costo', 'Sucursal CC', 'Razón Social CC', 'Superficie CC (ha)', 'Cantidad Asignada',
            'Estado Desarrollo', 'Cantidad/Ha', 'Total', 'Notas',
        ];
    }

    public function map($row): array
    {
        return [
            $row['outflow_id'],
            $row['tipo_gasto'],
            $row['date'],
            $row['month'],
            $row['supplier'],
            $row['branch_factura'],
            $row['company_reason_factura'],
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
            $row['branch_cc'],
            $row['company_reason_cc'],
            $row['surface'],
            $row['cantidad_asignada'],
            $row['development_state'],
            $row['cantidad_por_ha'],
            $row['total'],
            $row['notes'],
        ];
    }
}
