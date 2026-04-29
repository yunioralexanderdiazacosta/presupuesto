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

class ExportPayrollNominaPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $user = Auth::user();
        $seasonId = session('season_id');
        $month = $request->month;
        $monthId = (int) substr($month, 5);

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();

        $employees = Employee::with([
                'activeContract.bank', 'activeContract.accountType', 'activeContract.paymentMethod',
                'latestContract.bank', 'latestContract.accountType', 'latestContract.paymentMethod',
            ])
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('paternal_surname')
            ->get();

        $employeeIds = $employees->pluck('id');

        $yields = DailyYield::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('employee_id', $employeeIds)
            ->get(['employee_id', 'payment_type', 'amount', 'bonus_amount', 'target_price_bonus', 'workdays']);

        $yieldsByEmployee = $yields->groupBy('employee_id');

        $contractsByEmployee = Contract::where('team_id', $user->team_id)
            ->whereIn('employee_id', $employeeIds)
            ->get(['id', 'employee_id'])
            ->groupBy('employee_id');

        $allContractIds = $contractsByEmployee->flatten()->pluck('id');

        $bonusesByContract = MonthlyBonus::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'amount'])
            ->groupBy('contract_id');

        $discountsByContract = MonthlyDiscount::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'amount'])
            ->groupBy('contract_id');

        $overtimesByContract = OvertimeHour::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'hours', 'base_salary_snapshot', 'hourly_rate_factor_snapshot', 'overtime_multiplier_snapshot'])
            ->groupBy('contract_id');

        $rows = $employees->map(function ($emp) use (
            $yieldsByEmployee, $contractsByEmployee, $bonusesByContract,
            $discountsByContract, $overtimesByContract
        ) {
            $empYields = $yieldsByEmployee->get($emp->id, collect());
            $empContractIds = $contractsByEmployee->get($emp->id, collect())->pluck('id');

            $totalTratos       = $empYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia     = $empYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario  = $empYields->sum('bonus_amount');
            $totalBonusObjetivo = $empYields->sum('target_price_bonus');
            $totalBonusMensual = 0;
            $totalDescuentos   = 0;
            $totalHorasExtra   = 0;

            foreach ($empContractIds as $cId) {
                $totalBonusMensual += $bonusesByContract->get($cId, collect())->sum('amount');
                $totalDescuentos   += $discountsByContract->get($cId, collect())->sum('amount');
                foreach ($overtimesByContract->get($cId, collect()) as $ot) {
                    $totalHorasExtra += round(
                        $ot->hours * $ot->base_salary_snapshot * $ot->hourly_rate_factor_snapshot * $ot->overtime_multiplier_snapshot
                    );
                }
            }

            $totalNeto = $totalTratos + $totalMontoDia + $totalBonusDiario + $totalBonusObjetivo
                + $totalBonusMensual + $totalHorasExtra - $totalDescuentos;

            if ($totalNeto == 0 && $empYields->isEmpty()) {
                return null;
            }

            $contract = $emp->activeContract ?? $emp->latestContract;
            return [
                'contract_id'         => $contract?->id ?? '—',
                'rut'                 => $emp->rut,
                'full_name'           => $emp->full_name,
                'bank_name'           => $contract?->bank?->name ?? '—',
                'account_type_name'   => $contract?->accountType?->name ?? '—',
                'account_number'      => $contract?->account_number ?? '—',
                'payment_method_name' => $contract?->paymentMethod?->name ?? '—',
                'total_neto'          => $totalNeto,
            ];
        })->filter()->values();

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $monthLabel = $monthNames[$monthId - 1] . ' ' . $startDate->year;
        $grandTotal = $rows->sum('total_neto');

        $pdf = Pdf::loadView('payroll-reports.nomina', [
            'rows'       => $rows,
            'monthLabel' => $monthLabel,
            'grandTotal' => $grandTotal,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("nomina-pago-{$month}.pdf");
    }
}
