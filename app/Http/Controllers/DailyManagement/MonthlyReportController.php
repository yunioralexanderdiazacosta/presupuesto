<?php

namespace App\Http\Controllers\DailyManagement;

use App\Http\Controllers\Controller;
use App\Models\DailyYield;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $user = Auth::user();
        $seasonId = session('season_id');
        $month = $request->month;

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // Generar array de fechas del mes
        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = $startDate->copy()->day($d)->format('Y-m-d');
        }

        // Tarjas del mes
        $yields = DailyYield::with(['laborType', 'laborRate', 'bonusType', 'costCenters.costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        $yieldsByEmployee = $yields->groupBy('employee_id');

        // Empleados con tarjas en el mes (independiente de si tienen contrato activo ahora)
        $employeeIds = $yieldsByEmployee->keys();
        $employees = Employee::with('latestContract')
            ->whereIn('id', $employeeIds)
            ->orderBy('paternal_surname')
            ->get();

        $employeesData = $employees->map(function ($e) use ($yieldsByEmployee, $dates) {
            $empYields = $yieldsByEmployee->get($e->id, collect());

            // Agrupar por fecha
            $yieldsByDate = $empYields->groupBy(fn($y) => Carbon::parse($y->date)->format('Y-m-d'));

            $days = [];
            $grandTotalAmount = 0;
            $grandTotalBonus = 0;
            $grandTotalTargetBonus = 0;
            $grandTotalWorkdays = 0;
            $daysWorked = 0;

            foreach ($dates as $date) {
                $dayYields = $yieldsByDate->get($date, collect());
                $dayAmount = $dayYields->sum('amount');
                $dayBonus = $dayYields->sum('bonus_amount');
                $dayTargetBonus = $dayYields->sum('target_price_bonus');
                $dayWorkdays = $dayYields->sum('workdays');

                $days[$date] = [
                    'amount' => $dayAmount,
                    'bonus' => $dayBonus,
                    'target_bonus' => $dayTargetBonus,
                    'workdays' => round((float) $dayWorkdays, 2),
                    'lines' => $dayYields->map(fn($y) => [
                        'payment_type' => $y->payment_type ?? 'trato',
                        'labor_type' => $y->laborType?->name,
                        'labor_rate' => $y->laborRate?->name,
                        'rate' => $y->rate,
                        'quantity' => $y->quantity,
                        'amount' => $y->amount,
                        'workdays' => $y->workdays,
                        'bonus_amount' => $y->bonus_amount,
                        'target_price' => $y->target_price,
                        'target_price_bonus' => $y->target_price_bonus,
                        'cost_center' => $y->costCenters->map(fn($cc) => $cc->costCenter?->name)->filter()->implode(', '),
                    ])->values(),
                ];

                $grandTotalAmount += $dayAmount;
                $grandTotalBonus += $dayBonus;
                $grandTotalTargetBonus += $dayTargetBonus;
                $grandTotalWorkdays += $dayWorkdays;
                if ($dayYields->isNotEmpty()) $daysWorked++;
            }

            return [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'rut' => $e->rut,
                'position' => $e->latestContract?->position ?? '',
                'net_salary' => $e->latestContract?->net_salary ?? 0,
                'days' => $days,
                'grand_total_amount' => $grandTotalAmount,
                'grand_total_bonus' => $grandTotalBonus,
                'grand_total_target_bonus' => $grandTotalTargetBonus,
                'grand_total_workdays' => round((float) $grandTotalWorkdays, 2),
                'days_worked' => $daysWorked,
            ];
        })->filter(fn($e) => $e['grand_total_amount'] > 0 || $e['grand_total_bonus'] > 0 || $e['grand_total_target_bonus'] > 0 || $e['grand_total_workdays'] > 0)
          ->values();

        return response()->json([
            'month' => $month,
            'dates' => $dates,
            'daysInMonth' => $daysInMonth,
            'employees' => $employeesData,
            'totals' => [
                'amount' => $employeesData->sum('grand_total_amount'),
                'bonus' => $employeesData->sum('grand_total_bonus'),
                'target_bonus' => $employeesData->sum('grand_total_target_bonus'),
                'workdays' => round($employeesData->sum('grand_total_workdays'), 2),
            ],
        ]);
    }
}
