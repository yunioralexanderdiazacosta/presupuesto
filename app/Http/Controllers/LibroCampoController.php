<?php

namespace App\Http\Controllers;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use App\Models\CostCenter;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class LibroCampoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        $libroCampo = $this->getLibroCampoData($teamId, $seasonId);

        // Opciones para filtros
        $costCenterOptions = CostCenter::where('season_id', $seasonId)
            ->whereHas('companyReason', fn($q) => $q->where('team_id', $teamId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        $orderOptions = ApplicationOrder::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->orderByDesc('id')
            ->get(['id', 'date'])
            ->map(fn($o) => ['value' => $o->id, 'label' => '#' . $o->id . ' - ' . \Carbon\Carbon::parse($o->date)->format('d/m/Y')]);

        $productIds = AgrochemicalOutflow::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->distinct()
            ->pluck('product_id');

        $productOptions = Product::whereIn('id', $productIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]);

        return Inertia::render('LibroCampo/Index', [
            'libroCampo' => $libroCampo,
            'costCenterOptions' => $costCenterOptions,
            'orderOptions' => $orderOptions,
            'productOptions' => $productOptions,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $libroCampo = $this->getLibroCampoData($user->team_id, session('season_id'));

        // Aplicar filtros si vienen como query params
        $costCenterId = $request->query('cost_center_id');
        $orderId = $request->query('order_id');
        $productId = $request->query('product_id');

        if ($costCenterId) {
            $libroCampo = array_values(array_filter($libroCampo, fn($cc) => $cc['cost_center_id'] == $costCenterId));
        }

        if ($orderId || $productId) {
            $productName = null;
            if ($productId) {
                $productName = \App\Models\Product::find($productId)?->name;
            }

            $libroCampo = array_values(array_filter(array_map(function ($cc) use ($orderId, $productName) {
                $rows = $cc['rows'];
                if ($orderId) {
                    $rows = array_filter($rows, fn($r) => $r['orden_id'] == $orderId);
                }
                if ($productName) {
                    $rows = array_filter($rows, fn($r) => $r['producto'] === $productName);
                }
                $cc['rows'] = array_values($rows);
                return $cc;
            }, $libroCampo), fn($cc) => count($cc['rows']) > 0));
        }

        $pdf = Pdf::loadView('pdfs.libro-campo', [
            'libroCampo' => $libroCampo,
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'libro-de-campo-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->stream($filename);
    }

    private function getLibroCampoData($teamId, $seasonId)
    {
        $costCenters = CostCenter::where('season_id', $seasonId)
            ->whereHas('companyReason', function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            })
            ->with(['variety', 'fruit'])
            ->orderBy('name')
            ->get();

        $outflows = AgrochemicalOutflow::with([
            'applicationOrder.orderProducts.product.unit',
            'applicationOrder.orderProducts.unit',
            'applicationOrder.phenologicalStage',
            'applicationOrder.orderCostCenters.costCenter',
            'product.unit',
            'product:id,name,active_ingredient,unit_id',
            'costCenter:id,name,surface,variety_id,fruit_id',
            'costCenter.variety:id,name',
            'costCenter.fruit:id,name',
        ])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->orderBy('date', 'asc')
            ->get();

        return $costCenters->map(function ($cc) use ($outflows) {
            $ccOutflows = $outflows->where('cost_center_id', $cc->id);

            if ($ccOutflows->isEmpty()) {
                return null;
            }

            $rows = $ccOutflows->groupBy(function ($outflow) {
                return $outflow->application_order_id . '-' . $outflow->product_id;
            })->map(function ($group) use ($cc) {
                $outflow = $group->first();
                $order = $outflow->applicationOrder;
                $product = $outflow->product;

                $orderProduct = null;
                if ($order) {
                    $orderProduct = $order->orderProducts
                        ->where('product_id', $product->id)
                        ->first();
                }

                $fechaAplic = $outflow->date;
                $carencia = $orderProduct->carencia ?? null;
                $reingreso = $orderProduct->reingreso ?? null;

                // Mojamiento real = (maquinadas × volumen_equipo) / superficie_total_aplicada
                $maquinadas = $outflow->maquinadas ?? 0;
                $volumenEquipo = $order->volume ?? 0;
                $superficieTotalAplicada = 0;
                if ($order && $order->orderCostCenters) {
                    $superficieTotalAplicada = $order->orderCostCenters->sum(fn($occ) => $occ->costCenter->surface ?? 0);
                }
                $mojamientoReal = $superficieTotalAplicada > 0
                    ? round(($maquinadas * $volumenEquipo) / $superficieTotalAplicada)
                    : null;

                return [
                    'fecha_aplic' => $fechaAplic,
                    'limite_proteccion' => $reingreso && $fechaAplic
                        ? \Carbon\Carbon::parse($fechaAplic)->addDays($reingreso)->format('Y-m-d')
                        : null,
                    'orden_id' => $order->id ?? null,
                    'producto' => $product->name ?? '-',
                    'ingrediente_activo' => $product->active_ingredient ?? '-',
                    'carencia' => $carencia,
                    'reingreso' => $reingreso,
                    'cosecha_desde' => $carencia && $fechaAplic
                        ? \Carbon\Carbon::parse($fechaAplic)->addDays($carencia)->format('Y-m-d')
                        : null,
                    'tractor' => $order->tractors ?? '-',
                    'equipo' => $order->equipments ?? '-',
                    'operario' => $order->operators ?? '-',
                    'dosis_100' => $orderProduct->dosis_por_100 ?? null,
                    'dosis_ha' => $orderProduct->dosis_por_hectarea ?? null,
                    'unidad' => $product->unit->name ?? ($orderProduct->unit->name ?? '-'),
                    'mojamiento' => $mojamientoReal,
                    'maquinadas' => $maquinadas,
                    'cantidad' => $group->sum('quantity'),
                    'etapa_fenologica' => $order->phenologicalStage->name ?? '-',
                ];
            })->values()->toArray();

            return [
                'cost_center_id' => $cc->id,
                'cuartel' => $cc->name,
                'variedad' => $cc->variety->name ?? '-',
                'fruta' => $cc->fruit->name ?? '-',
                'superficie' => $cc->surface,
                'rows' => $rows,
            ];
        })->filter()->values()->toArray();
    }
}
