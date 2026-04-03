<?php

namespace App\Http\Controllers\DailyYields;

use App\Http\Controllers\Controller;
use App\Models\BonusType;
use App\Models\CostCenter;
use App\Models\DailyAttendance;
use App\Models\DailyYield;
use App\Models\Employee;
use App\Models\LaborType;
use App\Models\LaborRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyYieldController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $date = $request->get('date', now()->format('Y-m-d'));
        $maxHoursPerDay = 8; // PENDIENTE: obtener del Schedule del contrato

        // Asistencia del día (keyed by employee_id)
        $attendances = DailyAttendance::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $hasAttendance = $attendances->isNotEmpty();

        // Tarjas existentes agrupadas por empleado
        $allYields = DailyYield::with(['laborType', 'laborRate', 'bonusType', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->orderBy('id')
            ->get();

        $yieldsByEmployee = $allYields->groupBy('employee_id');

        // TODOS los empleados con contrato activo
        $employees = Employee::with('activeContract')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->whereHas('activeContract')
            ->orderBy('paternal_surname')
            ->get()
            ->map(function ($e) use ($attendances, $yieldsByEmployee, $maxHoursPerDay) {
                $att = $attendances->get($e->id);
                $empYields = $yieldsByEmployee->get($e->id, collect());
                $totalHours = $empYields->sum('hours');
                $totalAmount = $empYields->sum('amount');
                $totalBonus = $empYields->sum('bonus_amount');

                return [
                    'id' => $e->id,
                    'full_name' => $e->full_name,
                    'rut' => $e->rut,
                    'position' => $e->activeContract?->position ?? '',
                    'is_present' => $att ? $att->is_present : null, // null = sin asistencia
                    'yields' => $empYields->map(fn($y) => [
                        'id' => $y->id,
                        'labor_type_id' => $y->labor_type_id,
                        'labor_type_name' => $y->laborType?->name,
                        'labor_rate_id' => $y->labor_rate_id,
                        'labor_rate_name' => $y->laborRate?->name,
                        'rate' => $y->rate,
                        'quantity' => $y->quantity,
                        'amount' => $y->amount,
                        'hours' => $y->hours,
                        'bonus_type_id' => $y->bonus_type_id,
                        'bonus_type_name' => $y->bonusType?->name,
                        'bonus_amount' => $y->bonus_amount,
                        'cost_center_id' => $y->cost_center_id,
                        'cost_center_name' => $y->costCenter?->name,
                        'observations' => $y->observations,
                    ])->values(),
                    'total_hours' => round((float)$totalHours, 1),
                    'remaining_hours' => round($maxHoursPerDay - (float)$totalHours, 1),
                    'total_amount' => $totalAmount,
                    'total_bonus' => $totalBonus,
                    'yield_count' => $empYields->count(),
                ];
            });

        // Catálogos
        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($l) => [
                'value' => $l->id,
                'label' => $l->name,
            ]);

        $laborRates = LaborRate::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($lr) => [
                'value' => $lr->id,
                'label' => $lr->name,
                'labor_type_id' => $lr->labor_type_id,
                'rate' => $lr->rate,
            ]);

        $bonusTypes = BonusType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($b) => [
                'value' => $b->id,
                'label' => $b->name,
                'default_amount' => $b->default_amount,
            ]);

        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        // Resumen global
        $totalAmount = $allYields->sum('amount');
        $totalBonus = $allYields->sum('bonus_amount');
        $totalHours = $allYields->sum('hours');
        $employeesWithYields = $allYields->pluck('employee_id')->unique()->count();
        $presentCount = $attendances->where('is_present', true)->count();
        $absentCount = $attendances->where('is_present', false)->count();

        return Inertia::render('DailyYields/Index', [
            'employees' => $employees,
            'laborTypes' => $laborTypes,
            'laborRates' => $laborRates,
            'bonusTypes' => $bonusTypes,
            'costCenters' => $costCenters,
            'selectedDate' => $date,
            'hasAttendance' => $hasAttendance,
            'maxHoursPerDay' => $maxHoursPerDay,
            'summary' => [
                'totalEmployees' => $employees->count(),
                'presentCount' => $presentCount,
                'absentCount' => $absentCount,
                'employeesWithYields' => $employeesWithYields,
                'totalAmount' => $totalAmount,
                'totalBonus' => $totalBonus,
                'totalHours' => round((float)$totalHours, 1),
            ],
        ]);
    }
}
