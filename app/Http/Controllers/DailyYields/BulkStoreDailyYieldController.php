<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\DailyYield;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkStoreDailyYieldController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date'           => 'required|date',
            'labor_type_id'  => 'required|exists:labor_types,id',
            'workdays'       => 'required|numeric|min:0.1|max:1',
            'bonus_type_id'  => 'nullable|exists:bonus_types,id',
            'bonus_amount'   => 'nullable|integer|min:0',
            'cost_center_ids'   => 'nullable|array',
            'cost_center_ids.*' => 'exists:cost_centers,id',
            'observations'   => 'nullable|string|max:500',
            'employees'      => 'required|array|min:1',
            'employees.*.employee_id' => 'required|exists:employees,id',
            'employees.*.rate'        => 'required|integer|min:0',
        ]);

        $user      = Auth::user();
        $date      = $request->date;
        $teamId    = $user->team_id;
        $seasonId  = session('season_id');

        $saved   = 0;
        $skipped = 0;

        DB::transaction(function () use ($request, $user, $date, $teamId, $seasonId, &$saved, &$skipped) {
            foreach ($request->employees as $entry) {
                $employeeId = $entry['employee_id'];
                $rate       = $entry['rate'];

                // Resolver contrato vigente
                $contract = Contract::where('team_id', $teamId)
                    ->where('employee_id', $employeeId)
                    ->where('contract_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->where('is_active', true)
                          ->orWhereHas('terminations', fn($t) => $t->where('fecha_termino', '>=', $date));
                    })
                    ->orderByDesc('contract_date')
                    ->first();

                if (!$contract) {
                    $skipped++;
                    continue;
                }

                // Verificar que no exista ya una tarja "al día" para ese empleado esa fecha
                $exists = DailyYield::where('employee_id', $employeeId)
                    ->where('date', $date)
                    ->where('payment_type', 'dia')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $workdays    = (float) $request->workdays;
                $quantity    = 1;
                $amount      = $rate * $quantity;

                $yield = DailyYield::create([
                    'employee_id'        => $employeeId,
                    'contract_id'        => $contract->id,
                    'date'               => $date,
                    'payment_type'       => 'dia',
                    'labor_type_id'      => $request->labor_type_id,
                    'labor_rate_id'      => null,
                    'rate'               => $rate,
                    'quantity'           => $quantity,
                    'amount'             => $amount,
                    'workdays'           => $workdays,
                    'bonus_type_id'      => $request->bonus_type_id ?? null,
                    'bonus_amount'       => $request->bonus_amount ?? 0,
                    'target_price'       => null,
                    'target_price_bonus' => null,
                    'team_id'            => $teamId,
                    'season_id'          => $seasonId,
                    'user_id'            => $user->id,
                    'observations'       => $request->observations ?? null,
                ]);

                if (!empty($request->cost_center_ids)) {
                    foreach ($request->cost_center_ids as $ccId) {
                        $yield->costCenters()->create(['cost_center_id' => $ccId]);
                    }
                }

                $saved++;
            }
        });

        return redirect()->back()->with('success', "Se guardaron {$saved} tarjas." . ($skipped > 0 ? " {$skipped} omitidas (sin contrato o ya existentes)." : ''));
    }
}
