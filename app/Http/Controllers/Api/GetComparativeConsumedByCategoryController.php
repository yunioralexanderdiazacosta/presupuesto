<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\OutflowProrationTrait;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GetComparativeConsumedByCategoryController extends Controller
{
    use OutflowProrationTrait;

    /**
     * Consumido (outflows) agrupado por Nivel1/Nivel2/Nivel3 y mes, para el
     * selector "Real: Facturado | Consumido" del Detalle Mensual por Categoría
     * del Comparative Dashboard. Se carga bajo demanda (no en el payload inicial)
     * porque requiere recorrer todos los outflows de la temporada con prorrateo.
     *
     * IMPORTANTE: aquí la clasificación es la propia del outflow (o.level3_id) y
     * la razón social se prorratea por superficie de centro de costo, a diferencia
     * de Facturado que usa el level3 del producto y la razón social de la factura.
     * Por eso las categorías pueden no coincidir 1 a 1 entre ambas vistas.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'include_investments'  => 'nullable|boolean',
            'company_reason_ids'   => 'nullable|array',
            'company_reason_ids.*' => 'integer',
        ]);

        $user      = Auth::user();
        $team_id   = $user->team_id;
        $season_id = session('season_id');

        if (!$season_id) {
            return response()->json(['error' => 'Sin temporada activa'], 422);
        }

        $includeInvestments = filter_var($request->input('include_investments', true), FILTER_VALIDATE_BOOLEAN);
        $companyReasonIds = collect($request->input('company_reason_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $company_reason_id = count($companyReasonIds) > 0 ? $companyReasonIds : null;

        $map = [];

        try {
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with([
                    'invoiceProduct:id,unit_price,invoice_id',
                    'creditDebitNoteItem:id,unit_price,credit_debit_note_id',
                    'costCenters.costCenter:id,company_reason_id,surface',
                    'operation:id,name',
                    'level3:id,name,level2_id',
                    'level3.level2:id,name,level1_id',
                    'level3.level2.level1:id,name',
                ])
                ->get();

            foreach ($outflows as $outflow) {
                if (!$this->outflowMatchesCompanyReason($outflow, $company_reason_id)) continue;

                // Mes de la SALIDA (fecha propia del outflow), no de la factura/nota de origen
                if (!$outflow->date) continue;
                $monthId = (int) date('n', strtotime($outflow->date));

                $isInvestment = $outflow->operation && stripos($outflow->operation->name, 'inversion') !== false;
                if ($isInvestment && !$includeInvestments) continue;

                $amount = $this->proratedOutflowAmount($outflow, $company_reason_id);
                if ($amount == 0.0) continue;

                $level3 = $outflow->level3;
                $level2 = $level3?->level2;
                $level1 = $level2?->level1;

                $level1Name = $level1->name ?? 'Sin Clasificar';
                $level2Name = $level2->name ?? 'Sin Clasificar';
                $level3Name = $level3->name ?? 'Sin Clasificar';

                $key = $level1Name . '||' . $level2Name . '||' . $level3Name;
                if (!isset($map[$key])) {
                    $map[$key] = [
                        'level1'  => $level1Name,
                        'level2'  => $level2Name,
                        'level3'  => $level3Name,
                        'monthly' => [],
                    ];
                }
                $map[$key]['monthly'][$monthId] = ($map[$key]['monthly'][$monthId] ?? 0) + $amount;
            }
        } catch (\Exception $e) {
            Log::error('Error en GetComparativeConsumedByCategoryController: ' . $e->getMessage());
            return response()->json(['error' => 'Error calculando consumido por categoría'], 500);
        }

        return response()->json(['rows' => array_values($map)]);
    }
}
