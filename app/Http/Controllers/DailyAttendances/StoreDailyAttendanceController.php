<?php

namespace App\Http\Controllers\DailyAttendances;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyAttendances\StoreDailyAttendanceRequest;
use App\Models\Contract;
use App\Models\DailyAttendance;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreDailyAttendanceController extends Controller
{
    public function __invoke(StoreDailyAttendanceRequest $request)
    {
        $user     = Auth::user();
        $seasonId = session('season_id');
        $date     = $request->date;

        // Contratos vigentes en la fecha de la asistencia (activos o terminados después)
        $activeContracts = Contract::where('team_id', $user->team_id)
            ->where('contract_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->where('is_active', true)
                  ->orWhereHas('terminations', fn($t) => $t->where('fecha_termino', '>=', $date));
            })
            ->orderByDesc('contract_date')
            ->pluck('id', 'employee_id'); // [employee_id => contract_id]

        foreach ($request->attendances as $att) {
            DailyAttendance::updateOrCreate(
                [
                    'employee_id' => $att['employee_id'],
                    'date'        => $request->date,
                    'team_id'     => $user->team_id,
                ],
                [
                    'contract_id'              => $activeContracts[$att['employee_id']] ?? null,
                    'is_present'               => $att['is_present'],
                    'estimated_labor_type_id'  => $att['estimated_labor_type_id'] ?? null,
                    'estimated_cost_center_id' => $att['estimated_cost_center_id'] ?? null,
                    'season_id'                => $seasonId,
                    'registered_by'            => $user->id,
                ]
            );
        }

        return redirect()->back()
            ->with('success', 'Asistencia registrada correctamente.');
    }
}
