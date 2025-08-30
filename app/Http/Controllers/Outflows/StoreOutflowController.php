<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutflowRequest;
use App\Models\Outflow;
use Illuminate\Support\Facades\Auth;

class StoreOutflowController extends Controller
{
    public function __invoke(OutflowRequest $request)
    {
        $userId = Auth::id();
        $outflows = $request->input('outflows', []);
        $saved = 0;
        $teamId = Auth::user()->team_id ?? null;
        $seasonId = session('season_id');
        foreach ($outflows as $outflowData) {
            // Determinar si es factura o nota de débito
            $data = [
                'invoice_product_id' => $outflowData['tipo'] === 'factura' ? ($outflowData['invoice_product_id'] ?? null) : null,
                'credit_debit_note_item_id' => $outflowData['tipo'] === 'nota_debito' ? ($outflowData['credit_debit_note_item_id'] ?? null) : null,
                'user_id' => $userId,
                'team_id' => $teamId,
                'season_id' => $seasonId,
                'project_id' => $outflowData['project_id'] ?? null,
                'operation_id' => $outflowData['operation_id'] ?? null,
                'machinery_id' => $outflowData['machinery_id'] ?? null,
                'quantity' => $outflowData['quantity'] ?? null,
                'notes' => $outflowData['notes'] ?? null,
                'date' => now(),
            ];
            $outflow = \App\Models\Outflow::create($data);
            if (isset($outflowData['cost_center_ids']) && is_array($outflowData['cost_center_ids'])) {
                foreach ($outflowData['cost_center_ids'] as $costCenterId) {
                    $outflow->costCenters()->create([
                        'cost_center_id' => $costCenterId,
                        'observations' => $outflowData['notes'] ?? null,
                    ]);
                }
            }
            $saved++;
        }
        return redirect()->route('outflows.index')->with('success', "$saved salidas registradas correctamente.");
    }
}
