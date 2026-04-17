<?php

namespace App\Http\Controllers\DailyManagement;

use App\Http\Controllers\Controller;
use App\Models\DailyYield;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportMonthlyPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'mode' => 'required|in:planilla,detalle',
            'employee_id' => 'nullable|integer',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'integer',
        ]);

        $user = Auth::user();
        $seasonId = session('season_id');
        $month = $request->month;

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = $startDate->copy()->day($d)->format('Y-m-d');
        }

        $yieldsQuery = DailyYield::with(['laborType', 'laborRate', 'bonusType', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        $employeeIds = $request->employee_ids ?? ($request->employee_id ? [$request->employee_id] : []);

        if (!empty($employeeIds)) {
            $yieldsQuery->whereIn('employee_id', $employeeIds);
        }

        $yields = $yieldsQuery->orderBy('date')->orderBy('employee_id')->get();
        $yieldsByEmployee = $yields->groupBy('employee_id');

        $employeesQuery = Employee::with('activeContract')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->whereHas('activeContract')
            ->orderBy('paternal_surname');

        if (!empty($employeeIds)) {
            $employeesQuery->whereIn('id', $employeeIds);
        }

        $employees = $employeesQuery->get();

        $employeesData = $employees->map(function ($e) use ($yieldsByEmployee, $dates) {
            $empYields = $yieldsByEmployee->get($e->id, collect());
            $yieldsByDate = $empYields->groupBy(fn($y) => Carbon::parse($y->date)->format('Y-m-d'));

            $days = [];
            $grandTotalAmount = 0;
            $grandTotalBonus = 0;
            $grandTotalWorkdays = 0;

            foreach ($dates as $date) {
                $dayYields = $yieldsByDate->get($date, collect());
                $days[$date] = [
                    'amount' => $dayYields->sum('amount'),
                    'bonus' => $dayYields->sum('bonus_amount'),
                    'workdays' => round((float) $dayYields->sum('workdays'), 2),
                    'lines' => $dayYields,
                ];
                $grandTotalAmount += $days[$date]['amount'];
                $grandTotalBonus += $days[$date]['bonus'];
                $grandTotalWorkdays += $days[$date]['workdays'];
            }

            return [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'rut' => $e->rut,
                'position' => $e->activeContract?->position ?? '',
                'days' => $days,
                'grand_total_amount' => $grandTotalAmount,
                'grand_total_bonus' => $grandTotalBonus,
                'grand_total_workdays' => round((float) $grandTotalWorkdays, 2),
            ];
        })->filter(fn($e) => $e['grand_total_amount'] > 0 || $e['grand_total_bonus'] > 0)
          ->values();

        $viewName = $request->mode === 'detalle'
            ? 'pdfs.monthly-yields-detail'
            : 'pdfs.monthly-yields-planilla';

        $pdf = Pdf::loadView($viewName, [
            'employees' => $employeesData,
            'dates' => $dates,
            'month' => $month,
            'daysInMonth' => $daysInMonth,
            'team' => $user->team,
        ]);

        $orientation = $request->mode === 'planilla' ? 'landscape' : 'portrait';
        $pdf->setPaper('letter', $orientation);

        $filename = 'tarjas-' . $request->mode . '-' . $month . '.pdf';
        return $pdf->stream($filename);
    }
}
