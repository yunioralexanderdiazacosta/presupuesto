<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outflow;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ConsolidatedOutflowsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        // Consulta optimizada con eager loading
        $query = Outflow::with([
            'invoiceProduct.product.unit',
            'invoiceProduct.invoice.supplier',
            'invoiceProduct.invoice.typeDocument',
            'creditDebitNoteItem.product.unit',
            'creditDebitNoteItem.creditDebitNote.supplier',
            'project',
            'operation',
            'machinery',
            'costCenters.costCenter.developmentState',
            'level3.level2.level1'
        ])
        ->where('team_id', $user->team_id)
        ->where('season_id', $season_id);

        // Filtro de búsqueda
        if ($term) {
            $query->where(function($q) use ($term) {
                $q->whereHas('invoiceProduct.product', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('creditDebitNoteItem.product', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('invoiceProduct.invoice.supplier', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('creditDebitNoteItem.creditDebitNote.supplier', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('project', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('costCenters.costCenter', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                });
            });
        }

        // Obtener outflows y expandir por centro de costo
        $outflows = $query->orderBy('id', 'desc')->get();

        // Transformar datos: expandir cada outflow por sus centros de costo
        $expandedData = [];
        
        foreach ($outflows as $outflow) {
            // Determinar origen (factura o nota débito)
            $isInvoice = !is_null($outflow->invoice_product_id);
            
            // Datos comunes de la salida
            $commonData = [
                'outflow_id' => $outflow->id,
                'date' => $outflow->date ? \Carbon\Carbon::parse($outflow->date)->format('d-m-Y') : null,
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
                'project' => $outflow->project->name ?? null,
                'operation' => $outflow->operation->name ?? null,
                'machinery' => $outflow->machinery->name ?? null,
                'notes' => $outflow->notes,
                'level1_name' => $outflow->level3->level2->level1->name ?? null,
                'level2_name' => $outflow->level3->level2->name ?? null,
                'level3_name' => $outflow->level3->name ?? null,
            ];

            // Calcular superficie total de los CC asociados
            $totalSuperficie = $outflow->costCenters->sum(function($occ) {
                return $occ->costCenter->surface ?? 0;
            });

            // Calcular cantidad por hectárea
            $cantidadPorHa = $totalSuperficie > 0 
                ? $outflow->quantity / $totalSuperficie 
                : 0;

            // Si no hay centros de costo, agregar una fila sin CC
            if ($outflow->costCenters->isEmpty()) {
                $expandedData[] = array_merge($commonData, [
                    'cost_center_id' => null,
                    'cost_center_name' => '-',
                    'surface' => 0,
                    'cantidad_asignada' => $outflow->quantity,
                    'development_state' => null,
                    'total_superficie' => 0,
                    'cantidad_por_ha' => 0,
                    'total' => $outflow->quantity * ($commonData['unit_price']),
                ]);
                continue;
            }

            // Expandir: una fila por cada centro de costo
            foreach ($outflow->costCenters as $occ) {
                $superficie = $occ->costCenter->surface ?? 0;
                
                // Calcular cantidad asignada y total
                if ($superficie == 0) {
                    // Si no hay superficie, se asigna toda la cantidad (no hay prorrateo)
                    $cantidadAsignada = $outflow->quantity;
                    $totalCalculado = $outflow->quantity * $commonData['unit_price'];
                } else {
                    // Si hay superficie, se prorratea
                    $cantidadAsignada = $superficie * $cantidadPorHa;
                    $totalCalculado = $cantidadAsignada * $commonData['unit_price'];
                }

                $expandedData[] = array_merge($commonData, [
                    'cost_center_id' => $occ->costCenter->id,
                    'cost_center_name' => $occ->costCenter->name,
                    'surface' => $superficie, // Mostrar el valor real (puede ser 0)
                    'cantidad_asignada' => round($cantidadAsignada, 2),
                    'development_state' => $occ->costCenter->developmentState->name ?? null,
                    'total_superficie' => $totalSuperficie,
                    'cantidad_por_ha' => round($cantidadPorHa, 4),
                    'total' => round($totalCalculado, 2),
                    'cc_observations' => $occ->observations,
                ]);
            }
        }

        // Sin paginación - mostrar todos los resultados
        return Inertia::render('ConsolidatedOutflows', [
            'outflows' => [
                'data' => $expandedData,
                'total' => count($expandedData),
            ],
            'term' => $term,
        ]);
    }
}
