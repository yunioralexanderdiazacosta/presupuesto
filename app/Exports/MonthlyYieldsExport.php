<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\DailyYield;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MonthlyYieldsExport implements FromView, ShouldAutoSize
{
    protected string $month;
    protected string $mode;

    public function __construct(string $month, string $mode = 'planilla')
    {
        $this->month = $month;
        $this->mode = $mode;
    }

    public function view(): View
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        $startDate = Carbon::parse($this->month . '-01');
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dates[] = $startDate->copy()->day($d)->format('Y-m-d');
        }

        $yields = DailyYield::with(['laborType.level3', 'laborRate', 'bonusType', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('contract_id')
            ->orderBy('date')
            ->orderBy('contract_id')
            ->get();

        $yieldsByContract = $yields->groupBy('contract_id');

        $contractIds = $yieldsByContract->keys();
        $contracts = Contract::with('employee')
            ->whereIn('id', $contractIds)
            ->get()
            ->keyBy('id');

        $employeesData = $yieldsByContract->map(function ($contractYields, $contractId) use ($contracts, $dates) {
            $contract = $contracts->get($contractId);
            $employee = $contract?->employee;
            $yieldsByDate = $contractYields->groupBy(fn($y) => Carbon::parse($y->date)->format('Y-m-d'));

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
                'id'                   => $contractId,
                'contract_id'          => $contractId,
                'full_name'            => $employee?->full_name ?? '—',
                'rut'                  => $employee?->rut ?? '—',
                'position'             => $contract?->position ?? '',
                'days'                 => $days,
                'grand_total_amount'   => $grandTotalAmount,
                'grand_total_bonus'    => $grandTotalBonus,
                'grand_total_workdays' => round((float) $grandTotalWorkdays, 2),
            ];
        })->filter(fn($e) => $e['grand_total_amount'] > 0 || $e['grand_total_bonus'] > 0 || $e['grand_total_workdays'] > 0)
          ->sortBy('full_name')
          ->values();

        $viewName = match($this->mode) {
            'detalle' => 'excels.monthly-yields-detail',
            'labor'   => 'excels.monthly-yields-labor',
            default   => 'excels.monthly-yields-planilla',
        };

        return view($viewName, [
            'employees' => $employeesData,
            'dates' => $dates,
            'month' => $this->month,
            'daysInMonth' => $daysInMonth,
        ]);
    }
}
