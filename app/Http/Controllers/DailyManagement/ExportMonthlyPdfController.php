<?php

namespace App\Http\Controllers\DailyManagement;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\DailyYield;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportMonthlyPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'month'         => 'required|date_format:Y-m',
            'mode'          => 'required|in:planilla,detalle',
            'contract_ids'   => 'nullable|array',
            'contract_ids.*' => 'integer',
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
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('contract_id');

        $filterContractIds = $request->contract_ids ?? [];

        if (!empty($filterContractIds)) {
            $yieldsQuery->whereIn('contract_id', $filterContractIds);
        }

        $yields = $yieldsQuery->orderBy('date')->orderBy('contract_id')->get();
        $yieldsByContract = $yields->groupBy('contract_id');

        $contractIds = $yieldsByContract->keys();
        $contractsQuery = Contract::with('employee')->whereIn('id', $contractIds);
        $contracts = $contractsQuery->get()->keyBy('id');

        $employeesData = $yieldsByContract->map(function ($contractYields, $contractId) use ($contracts, $dates) {
            $contract = $contracts->get($contractId);
            $employee = $contract?->employee;
            $yieldsByDate = $contractYields->groupBy(fn($y) => Carbon::parse($y->date)->format('Y-m-d'));

            $days = [];
            $grandTotalAmount = 0;
            $grandTotalBonus = 0;
            $grandTotalTargetBonus = 0;
            $grandTotalWorkdays = 0;

            foreach ($dates as $date) {
                $dayYields = $yieldsByDate->get($date, collect());
                $days[$date] = [
                    'amount'      => $dayYields->sum('amount'),
                    'bonus'       => $dayYields->sum('bonus_amount'),
                    'target_bonus'=> $dayYields->sum('target_price_bonus'),
                    'workdays'    => round((float) $dayYields->sum('workdays'), 2),
                    'lines'       => $dayYields,
                ];
                $grandTotalAmount       += $days[$date]['amount'];
                $grandTotalBonus        += $days[$date]['bonus'];
                $grandTotalTargetBonus  += $days[$date]['target_bonus'];
                $grandTotalWorkdays     += $days[$date]['workdays'];
            }

            return [
                'id'                       => $contractId,
                'contract_id'              => $contractId,
                'full_name'                => $employee?->full_name ?? '—',
                'rut'                      => $employee?->rut ?? '—',
                'position'                 => $contract?->position ?? '',
                'days'                     => $days,
                'grand_total_amount'       => $grandTotalAmount,
                'grand_total_bonus'        => $grandTotalBonus,
                'grand_total_target_bonus' => $grandTotalTargetBonus,
                'grand_total_workdays'     => round((float) $grandTotalWorkdays, 2),
            ];
        })->filter(fn($e) => $e['grand_total_amount'] > 0 || $e['grand_total_bonus'] > 0 || $e['grand_total_target_bonus'] > 0)
          ->sortBy('full_name')
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
