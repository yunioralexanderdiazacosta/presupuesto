<?php

namespace App\Http\Controllers\DailyManagement;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\BonusType;
use App\Models\CostCenter;
use App\Models\DailyAttendance;
use App\Models\DailyYield;
use App\Models\Employee;
use App\Models\LaborRate;
use App\Models\LaborType;
use App\Models\Level3;
use App\Models\Parcel;
use App\Models\Unit;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyManagementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        $date = $request->get('date', now()->format('Y-m-d'));
        $activeTab = $request->get('tab', 'attendance');

        // === HORARIO DE TRABAJO ===
        $schedule = WorkSchedule::firstOrCreate(
            ['team_id' => $user->team_id, 'season_id' => $seasonId],
            [
                'monday_hours' => 8, 'tuesday_hours' => 8, 'wednesday_hours' => 8,
                'thursday_hours' => 8, 'friday_hours' => 8,
                'saturday_hours' => 0, 'sunday_hours' => 0,
                'weekly_hours' => 40,
            ]
        );

        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;
        $scheduleHoursForDay = $schedule->hoursForDayOfWeek($dayOfWeek);
        $maxWorkdayPerDay = $scheduleHoursForDay > 0 ? 1.0 : 0;

        // === ASISTENCIA ===
        $attendances = DailyAttendance::with(['estimatedLaborType', 'estimatedCostCenter.parcel'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $hasAttendance = $attendances->isNotEmpty();

        // === TARJAS ===
        $allYields = DailyYield::with(['laborType', 'laborRate', 'bonusType', 'costCenters.costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->orderBy('id')
            ->get();

        $yieldsByEmployee = $allYields->groupBy('employee_id');

        // === EMPLEADOS (con datos de asistencia + tarjas) ===
        // Incluye empleados con contrato activo HOY + empleados cuyo contrato fue
        // terminado DESPUÉS de la fecha consultada (para poder digitar tarjas retroactivas)
        $contractsOnDate = Contract::where('team_id', $user->team_id)
            ->where('contract_date', '<=', $date)
            ->where(function ($q) use ($date) {
                // Contrato activo hoy
                $q->where('is_active', true)
                  // O contrato terminado en fecha posterior o igual a la consultada
                  ->orWhereHas('terminations', fn($t) => $t->where('fecha_termino', '>=', $date));
            })
            ->pluck('employee_id')
            ->unique();

        $employees = Employee::with(['contracts' => fn($q) => $q->where('team_id', request()->user()->team_id)->with('terminations')->orderByDesc('contract_date')])
            ->where('team_id', $user->team_id)
            ->whereIn('id', $contractsOnDate)
            ->orderBy('paternal_surname')
            ->get()
            ->map(function ($e) use ($attendances, $yieldsByEmployee, $maxWorkdayPerDay, $date) {
                // Contrato vigente en la fecha: activo hoy, O terminado en fecha >= $date
                // Usar format() para evitar comparación Carbon vs string
                $contract = $e->contracts
                    ->filter(function ($c) use ($date) {
                        if ($c->contract_date->format('Y-m-d') > $date) return false;
                        if ($c->is_active) return true;
                        return $c->terminations
                            ->filter(fn($t) => $t->fecha_termino->format('Y-m-d') >= $date)
                            ->isNotEmpty();
                    })
                    ->sortByDesc(fn($c) => $c->contract_date->format('Y-m-d'))
                    ->first();
                $att = $attendances->get($e->id);
                $empYields = $yieldsByEmployee->get($e->id, collect());
                $totalWorkdays = $empYields->sum('workdays');
                $totalAmount = $empYields->sum('amount');
                $totalBonus = $empYields->sum('bonus_amount');
                $totalTargetBonus = $empYields->sum('target_price_bonus');

                return [
                    'id' => $e->id,
                    'full_name' => $e->full_name,
                    'rut' => $e->rut,
                    'contract_id' => $contract?->id,
                    'position' => $contract?->position ?? '',
                    'base_salary' => $contract?->base_salary ?? 0,
                    'net_salary' => $contract?->net_salary ?? 0,
                    'daily_rate' => $contract?->net_salary ? round($contract->net_salary / 30) : 0,
                    'parcel_id' => $contract?->parcel_id,
                    'is_present' => $att ? $att->is_present : null,
                    'yields' => $empYields->map(fn($y) => [
                        'id' => $y->id,
                        'payment_type' => $y->payment_type ?? 'trato',
                        'labor_type_id' => $y->labor_type_id,
                        'labor_type_name' => $y->laborType?->name,
                        'is_absence' => $y->laborType?->is_absence ?? false,
                        'labor_rate_id' => $y->labor_rate_id,
                        'labor_rate_name' => $y->laborRate?->name,
                        'rate' => $y->rate,
                        'quantity' => $y->quantity,
                        'amount' => $y->amount,
                        'workdays' => $y->workdays,
                        'bonus_type_id' => $y->bonus_type_id,
                        'bonus_type_name' => $y->bonusType?->name,
                        'bonus_amount' => $y->bonus_amount,
                        'target_price' => $y->target_price,
                        'target_price_bonus' => $y->target_price_bonus,
                        'cost_center_ids' => $y->costCenters->pluck('cost_center_id')->toArray(),
                        'cost_center_names' => $y->costCenters->map(fn($cc) => $cc->costCenter?->name)->filter()->implode(', '),
                        'observations' => $y->observations,
                    ])->values(),
                    'total_workdays' => round((float) $totalWorkdays, 2),
                    'remaining_workdays' => $maxWorkdayPerDay > 0 ? round($maxWorkdayPerDay - (float) $totalWorkdays, 2) : null,
                    'total_amount' => $totalAmount,
                    'total_bonus' => $totalBonus,
                    'total_target_bonus' => $totalTargetBonus,
                    'yield_count' => $empYields->count(),
                ];
            })
            ->filter(fn($e) => $e['contract_id'] !== null) // excluir si no se resolvió contrato vigente
            ->values();

        // === CENTROS DE COSTO ===
        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        // === PARCELAS ===
        $parcels = Parcel::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]);

        // === CATÁLOGOS (formato select) ===
        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($l) => ['value' => $l->id, 'label' => $l->code . ' - ' . $l->name, 'is_absence' => $l->is_absence, 'is_paid' => $l->is_paid]);

        $laborRates = LaborRate::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($lr) => [
                'value' => $lr->id,
                'label' => $lr->code . ' - ' . $lr->name,
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

        // === CATÁLOGOS COMPLETOS (para tabs CRUD) ===
        $laborTypesCatalog = LaborType::with(['level3', 'unit'])
            ->where('team_id', $user->team_id)
            ->orderBy('code')
            ->get();

        $laborRatesCatalog = LaborRate::with(['laborType', 'unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->orderBy('code')
            ->get();

        $bonusTypesCatalog = BonusType::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get();

        $level3s = Level3::from('level3s as l3')
            ->join('level2s as l2', 'l2.id', 'l3.level2_id')
            ->join('level1s as l1', 'l1.id', 'l2.level1_id')
            ->select('l3.id', 'l3.name')
            ->where('l1.team_id', $user->team_id)
            ->where('l1.season_id', $seasonId)
            ->where('l2.name', 'like', '%mano de obra%')
            ->orderBy('l3.name')
            ->get()
            ->map(fn($l) => ['value' => $l->id, 'label' => $l->name]);

        $units = Unit::orderBy('name')->get()->map(fn($u) => ['value' => $u->id, 'label' => $u->name]);

        // === RESÚMENES ===
        $presentCount = $attendances->where('is_present', true)->count();
        $absentCount = $attendances->where('is_present', false)->count();

        return Inertia::render('DailyManagement/Index', [
            'employees' => $employees,
            'selectedDate' => $date,
            'activeTab' => $activeTab,
            'costCenters' => $costCenters,
            'parcels' => $parcels,
            'maxWorkdayPerDay' => $maxWorkdayPerDay,
            'hasAttendance' => $hasAttendance,
            // Asistencia
            'attendances' => $attendances,
            'attendanceSummary' => [
                'total' => $employees->count(),
                'present' => $presentCount,
                'absent' => $absentCount,
                'pending' => $employees->count() - $attendances->count(),
                'parcelSummary' => $attendances->where('is_present', true)
                    ->filter(fn($a) => $a->estimated_cost_center_id)
                    ->groupBy(fn($a) => $a->estimatedCostCenter?->parcel_id ?? 0)
                    ->map(function ($group) {
                        $parcelName = $group->first()->estimatedCostCenter?->parcel?->name ?? 'Sin parcela';
                        $laborGroups = $group->groupBy('estimated_labor_type_id')->map(function ($laborGroup) {
                            return [
                                'labor_name' => $laborGroup->first()->estimatedLaborType?->name ?? 'Sin labor',
                                'workers' => $laborGroup->count(),
                            ];
                        })->sortByDesc('workers')->values();

                        return [
                            'parcel_name' => $parcelName,
                            'labors' => $laborGroups,
                            'total_workers' => $group->count(),
                        ];
                    })->sortByDesc('total_workers')->values(),
            ],
            // Tarjas
            'yieldsSummary' => [
                'totalEmployees' => $employees->count(),
                'presentCount' => $presentCount,
                'absentCount' => $absentCount,
                'employeesWithYields' => $allYields->pluck('employee_id')->unique()->count(),
                'totalAmount' => $allYields->sum('amount'),
                'totalBonus' => $allYields->sum('bonus_amount'),
                'totalWorkdays' => round((float) $allYields->sum('workdays'), 2),
            ],
            // Selects
            'laborTypes' => $laborTypes,
            'laborRates' => $laborRates,
            'bonusTypes' => $bonusTypes,
            // Catálogos CRUD
            'laborTypesCatalog' => $laborTypesCatalog,
            'laborRatesCatalog' => $laborRatesCatalog,
            'bonusTypesCatalog' => $bonusTypesCatalog,
            'level3s' => $level3s,
            'units' => $units,
            // Horario
            'schedule' => $schedule,
        ]);
    }
}
