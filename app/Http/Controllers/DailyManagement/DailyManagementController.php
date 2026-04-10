<?php

namespace App\Http\Controllers\DailyManagement;

use App\Http\Controllers\Controller;
use App\Models\BonusType;
use App\Models\CostCenter;
use App\Models\DailyAttendance;
use App\Models\DailyYield;
use App\Models\Employee;
use App\Models\LaborRate;
use App\Models\LaborType;
use App\Models\Level3;
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
        $maxHoursPerDay = $schedule->hoursForDayOfWeek($dayOfWeek);

        // === ASISTENCIA ===
        $attendances = DailyAttendance::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $hasAttendance = $attendances->isNotEmpty();

        // === TARJAS ===
        $allYields = DailyYield::with(['laborType', 'laborRate', 'bonusType', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('date', $date)
            ->orderBy('id')
            ->get();

        $yieldsByEmployee = $allYields->groupBy('employee_id');

        // === EMPLEADOS (con datos de asistencia + tarjas) ===
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
                    'base_salary' => $e->activeContract?->base_salary ?? 0,
                    'net_salary' => $e->activeContract?->net_salary ?? 0,
                    'daily_rate' => $e->activeContract?->net_salary ? round($e->activeContract->net_salary / 30) : 0,
                    'is_present' => $att ? $att->is_present : null,
                    'yields' => $empYields->map(fn($y) => [
                        'id' => $y->id,
                        'payment_type' => $y->payment_type ?? 'trato',
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
                    'total_hours' => round((float) $totalHours, 1),
                    'remaining_hours' => $maxHoursPerDay > 0 ? round($maxHoursPerDay - (float) $totalHours, 1) : null,
                    'total_amount' => $totalAmount,
                    'total_bonus' => $totalBonus,
                    'yield_count' => $empYields->count(),
                ];
            });

        // === CENTROS DE COSTO ===
        $costCenters = CostCenter::where('season_id', $seasonId)
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        // === CATÁLOGOS (formato select) ===
        $laborTypes = LaborType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($l) => ['value' => $l->id, 'label' => $l->code . ' - ' . $l->name]);

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
            ->where('l2.name', 'mano de obra')
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
            'maxHoursPerDay' => $maxHoursPerDay,
            'hasAttendance' => $hasAttendance,
            // Asistencia
            'attendances' => $attendances,
            'attendanceSummary' => [
                'total' => $employees->count(),
                'present' => $presentCount,
                'absent' => $absentCount,
                'pending' => $employees->count() - $attendances->count(),
            ],
            // Tarjas
            'yieldsSummary' => [
                'totalEmployees' => $employees->count(),
                'presentCount' => $presentCount,
                'absentCount' => $absentCount,
                'employeesWithYields' => $allYields->pluck('employee_id')->unique()->count(),
                'totalAmount' => $allYields->sum('amount'),
                'totalBonus' => $allYields->sum('bonus_amount'),
                'totalHours' => round((float) $allYields->sum('hours'), 1),
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
