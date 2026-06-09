<?php

namespace App\Http\Controllers\Vacations;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Vacation;
use App\Services\VacationCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreVacationController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'employee_id'  => 'required|integer|exists:employees,id',
            'contract_id'  => 'required|integer|exists:contracts,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'notas'        => 'nullable|string|max:500',
        ]);

        $user    = Auth::user();
        $service = new VacationCalculatorService();

        // Verificar que el contrato pertenece al equipo
        $contract = Contract::where('id', $request->contract_id)
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->where('contract_type', 'Indefinido')
            ->firstOrFail();

        $start       = Carbon::parse($request->fecha_inicio);
        $end         = Carbon::parse($request->fecha_fin);
        $diasHabiles = $service->countBusinessDays($start, $end, $user->team_id);

        Vacation::create([
            'team_id'      => $user->team_id,
            'employee_id'  => $request->employee_id,
            'contract_id'  => $contract->id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'dias_habiles' => $diasHabiles,
            'notas'        => $request->notas,
            'created_by'   => $user->id,
        ]);

        return redirect()->route('vacations.index')
            ->with('success', 'Período de vacaciones registrado correctamente.');
    }
}
