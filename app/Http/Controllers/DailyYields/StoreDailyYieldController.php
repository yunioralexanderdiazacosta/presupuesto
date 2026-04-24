<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyYields\StoreDailyYieldRequest;
use App\Models\Contract;
use App\Models\DailyYield;
use App\Models\Termination;
use Illuminate\Support\Facades\Auth;

class StoreDailyYieldController extends Controller
{
    public function __invoke(StoreDailyYieldRequest $request)
    {
        $user = Auth::user();
        $date = $request->date;

        // Resolver contrato vigente para la fecha de la tarja
        // (activo hoy, o terminado en fecha >= fecha de la tarja)
        $contract = Contract::where('team_id', $user->team_id)
            ->where('employee_id', $request->employee_id)
            ->where('contract_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->where('is_active', true)
                  ->orWhereHas('terminations', fn($t) => $t->where('fecha_termino', '>=', $date));
            })
            ->orderByDesc('contract_date')
            ->first();

        if (!$contract) {
            return redirect()->back()
                ->with('error', 'El colaborador no tenía contrato vigente en la fecha indicada.');
        }

        $contractId = $contract->id;

        $yield = DailyYield::create([
            'employee_id'        => $request->employee_id,
            'contract_id'        => $contractId,
            'date'               => $request->date,
            'payment_type'       => $request->payment_type,
            'labor_type_id'      => $request->labor_type_id,
            'labor_rate_id'      => $request->payment_type === 'trato' ? $request->labor_rate_id : null,
            'rate'               => $request->rate,
            'quantity'           => $request->quantity,
            'amount'             => $request->rate * $request->quantity,
            'workdays'           => $request->workdays,
            'bonus_type_id'      => $request->bonus_type_id,
            'bonus_amount'       => $request->bonus_amount ?? 0,
            'target_price'       => $request->target_price ?? null,
            'target_price_bonus' => $request->target_price_bonus ?? null,
            'team_id'            => $user->team_id,
            'season_id'          => session('season_id'),
            'user_id'            => $user->id,
            'observations'       => $request->observations,
        ]);

        // Guardar centros de costo en tabla pivote
        if (!empty($request->cost_center_ids)) {
            foreach ($request->cost_center_ids as $ccId) {
                $yield->costCenters()->create(['cost_center_id' => $ccId]);
            }
        }

        return redirect()->back()
            ->with('success', 'Tarja registrada correctamente.');
    }
}
