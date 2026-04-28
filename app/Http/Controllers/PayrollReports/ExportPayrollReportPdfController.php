<?php

namespace App\Http\Controllers\PayrollReports;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\DailyYield;
use App\Models\Employee;
use App\Models\MonthlyBonus;
use App\Models\MonthlyDiscount;
use App\Models\OvertimeHour;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportPayrollReportPdfController extends Controller
{
    public function __invoke(Request $request, Employee $employee)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $user = Auth::user();
        $seasonId = session('season_id');

        abort_if($employee->team_id !== $user->team_id, 403);

        $month = $request->month;
        $monthId = (int) substr($month, 5);

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = $startDate->copy()->day($d)->format('Y-m-d');
        }

        // Daily yields
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
                ])->values()->toArray(),
            ];

            $totalTratos += $dayYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia += $dayYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario += $dayYields->sum('bonus_amount');
            $totalBonusObjetivo += $dayYields->sum('target_price_bonus');
            $totalWorkdays += $dayWorkdays;
        }

        $contractIds = Contract::where('team_id', $user->team_id)
            ->where('employee_id', $employee->id)
            ->pluck('id');

        $monthlyBonuses = MonthlyBonus::with(['bonusType', 'laborType', 'costCenters'])
            ->where('team_id', $user->team_id)
            ->whereIn('contract_id', $contractIds)
            ->where('month_id', $monthId)
            ->get()
            ->map(fn($b) => [
                'type' => $b->bonusType?->name ?? '-',
                'labor_type' => $b->laborType?->name,
                'amount' => $b->amount,
                'cost_centers' => $b->costCenters->pluck('name')->implode(', '),
                'observations' => $b->observations,
            ])->toArray();

        $monthlyDiscounts = MonthlyDiscount::with(['discountType'])
            ->where('team_id', $user->team_id)
            ->whereIn('contract_id', $contractIds)
            ->where('month_id', $monthId)
            ->get()
            ->map(fn($d) => [
                'type' => $d->discountType?->name ?? '-',
                'amount' => $d->amount,
                'observations' => $d->observations,
            ])->toArray();

        $overtimeHours = OvertimeHour::with(['overtimeType', 'laborType', 'costCenters'])
            ->where('team_id', $user->team_id)
            ->whereIn('contract_id', $contractIds)
            ->where('month_id', $monthId)
            ->get()
            ->map(fn($o) => [
                'type' => $o->overtimeType?->name ?? '-',
                'labor_type' => $o->laborType?->name,
                'hours' => $o->hours,
                'amount' => round(
                    $o->hours * $o->base_salary_snapshot * $o->hourly_rate_factor_snapshot * $o->overtime_multiplier_snapshot
                ),
                'cost_centers' => $o->costCenters->pluck('name')->implode(', '),
                'observations' => $o->observations,
            ])->toArray();

        $totalBonusMensual = collect($monthlyBonuses)->sum('amount');
        $totalDescuentos = collect($monthlyDiscounts)->sum('amount');
        $totalHorasExtra = collect($overtimeHours)->sum('amount');
        $totalNeto = $totalTratos + $totalMontoDia + $totalBonusDiario + $totalBonusObjetivo
            + $totalBonusMensual + $totalHorasExtra - $totalDescuentos;

        $monthNames = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $monthLabel = $monthNames[$monthId] . ' ' . substr($month, 0, 4);

        $employee->load('activeContract.companyReason');

        $pdf = Pdf::loadView('payroll-reports.show', [
            'employee' => [
                'full_name' => $employee->full_name,
                'rut' => $employee->rut,
                'position' => $employee->activeContract?->position ?? '',
                'base_salary' => $employee->activeContract?->base_salary ?? 0,
                'net_salary' => $employee->activeContract?->net_salary ?? 0,
                'contract_type' => $employee->activeContract?->contract_type ?? '',
                'company_reason' => $employee->activeContract?->companyReason?->name ?? '',
            ],
            'month' => $month,
            'monthLabel' => $monthLabel,
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
        ])->setPaper('a4', 'portrait');

        $filename = 'remuneraciones-' . str_replace(' ', '-', strtolower($employee->paternal_surname)) . '-' . $month . '.pdf';
        return $pdf->download($filename);
    }
}
