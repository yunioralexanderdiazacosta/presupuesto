<?php

namespace App\Http\Controllers\PayrollReports;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\DailyYield;
use App\Models\Employee;
use App\Models\MonthlyBonus;
use App\Models\MonthlyDiscount;
use App\Models\OvertimeHour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowPayrollReportController extends Controller
{
    public function __invoke(Request $request, Employee $employee)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $user = Auth::user();
        $seasonId = session('season_id');

        // Security: employee must belong to same team
        abort_if($employee->team_id !== $user->team_id, 403);

        $month = $request->month;
        $monthId = (int) substr($month, 5); // 1-12

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Build list of dates for the month
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = $startDate->copy()->day($d)->format('Y-m-d');
        }

        // Daily yields for this employee and month
        $yields = DailyYield::with(['laborType', 'laborRate', 'bonusType', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->get();

        $yieldsByDate = $yields->groupBy(fn($y) => Carbon::parse($y->date)->format('Y-m-d'));

        $days = [];
        $totalTratos = 0;
        $totalMontoDia = 0;
        $totalBonusDiario = 0;
        $totalBonusObjetivo = 0;
        $totalWorkdays = 0;
        $daysWorked = 0;

        foreach ($dates as $date) {
            $dayYields = $yieldsByDate->get($date, collect());

            if ($dayYields->isNotEmpty()) {
                $daysWorked++;
            }

            $dayWorkdays = $dayYields->sum('workdays');

            $days[$date] = [
                'amount' => $dayYields->sum('amount'),
                'bonus' => $dayYields->sum('bonus_amount'),
                'target_bonus' => $dayYields->sum('target_price_bonus'),
                'workdays' => round((float) $dayWorkdays, 2),
                'lines' => $dayYields->map(fn($y) => [
                    'payment_type' => $y->payment_type ?? 'trato',
                    'labor_type' => $y->laborType?->name,
                    'labor_rate' => $y->laborRate?->name,
                    'rate' => $y->rate,
                    'quantity' => $y->quantity,
                    'amount' => $y->amount,
                    'workdays' => $y->workdays,
                    'bonus_type' => $y->bonusType?->name,
                    'bonus_amount' => $y->bonus_amount,
                    'target_price_bonus' => $y->target_price_bonus,
                    'cost_center' => $y->costCenter?->name,
                ])->values(),
            ];

            $totalTratos += $dayYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia += $dayYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario += $dayYields->sum('bonus_amount');
            $totalBonusObjetivo += $dayYields->sum('target_price_bonus');
            $totalWorkdays += $dayWorkdays;
        }

        // All contracts for this employee (all historical, for lookups)
        $contractIds = Contract::where('team_id', $user->team_id)
            ->where('employee_id', $employee->id)
            ->pluck('id');

        // Monthly bonuses for this month
        $monthlyBonuses = MonthlyBonus::with(['bonusType', 'laborType', 'costCenters'])
            ->where('team_id', $user->team_id)
            ->whereIn('contract_id', $contractIds)
            ->where('month_id', $monthId)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'type' => $b->bonusType?->name ?? '-',
                'labor_type' => $b->laborType?->name,
                'amount' => $b->amount,
                'cost_centers' => $b->costCenters->pluck('name')->implode(', '),
                'observations' => $b->observations,
            ])
            ->values();

        $totalBonusMensual = $monthlyBonuses->sum('amount');

        // Monthly discounts for this month
        $monthlyDiscounts = MonthlyDiscount::with(['discountType'])
            ->where('team_id', $user->team_id)
            ->whereIn('contract_id', $contractIds)
            ->where('month_id', $monthId)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'type' => $d->discountType?->name ?? '-',
                'amount' => $d->amount,
                'observations' => $d->observations,
            ])
            ->values();

        $totalDescuentos = $monthlyDiscounts->sum('amount');

        // Overtime hours for this month
        $overtimeHours = OvertimeHour::with(['overtimeType', 'laborType', 'costCenters'])
            ->where('team_id', $user->team_id)
            ->whereIn('contract_id', $contractIds)
            ->where('month_id', $monthId)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'type' => $o->overtimeType?->name ?? '-',
                'labor_type' => $o->laborType?->name,
                'hours' => $o->hours,
                'amount' => round(
                    $o->hours * $o->base_salary_snapshot * $o->hourly_rate_factor_snapshot * $o->overtime_multiplier_snapshot
                ),
                'cost_centers' => $o->costCenters->pluck('name')->implode(', '),
                'observations' => $o->observations,
            ])
            ->values();

        $totalHorasExtra = $overtimeHours->sum('amount');

        $totalNeto = $totalTratos + $totalMontoDia + $totalBonusDiario + $totalBonusObjetivo
            + $totalBonusMensual + $totalHorasExtra - $totalDescuentos;

        $employee->load('activeContract.companyReason');

        return Inertia::render('PayrollReports/Show', [
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'rut' => $employee->rut,
                'position' => $employee->activeContract?->position ?? '',
                'base_salary' => $employee->activeContract?->base_salary ?? 0,
                'net_salary' => $employee->activeContract?->net_salary ?? 0,
                'contract_type' => $employee->activeContract?->contract_type ?? '',
                'company_reason' => $employee->activeContract?->companyReason?->name ?? '',
            ],
            'month' => $month,
            'dates' => $dates,
            'days' => $days,
            'monthlyBonuses' => $monthlyBonuses,
            'monthlyDiscounts' => $monthlyDiscounts,
            'overtimeHours' => $overtimeHours,
            'totals' => [
                'tratos' => $totalTratos,
                'monto_dia' => $totalMontoDia,
                'bonus_diario' => $totalBonusDiario,
                'bonus_objetivo' => $totalBonusObjetivo,
                'bonus_mensual' => $totalBonusMensual,
                'horas_extra' => $totalHorasExtra,
                'descuentos' => $totalDescuentos,
                'workdays' => round((float) $totalWorkdays, 2),
                'days_worked' => $daysWorked,
                'neto' => $totalNeto,
            ],
        ]);
    }
}
