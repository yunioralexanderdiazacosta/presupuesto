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

class ExportPayrollSueldosPdfController extends Controller
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
                'activeContract', 'latestContract',
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

            $totalTratos        = $empYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia      = $empYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario   = $empYields->sum('bonus_amount');
            $totalBonusObjetivo = $empYields->sum('target_price_bonus');
            $totalWorkdays      = round((float) $empYields->sum('workdays'), 2);
            $totalBonusMensual  = 0;
            $totalDescuentos    = 0;
            $totalHorasExtra    = 0;

            foreach ($empContractIds as $cId) {
                $totalBonusMensual += $bonusesByContract->get($cId, collect())->sum('amount');
                $totalDescuentos   += $discountsByContract->get($cId, collect())->sum('amount');
                foreach ($overtimesByContract->get($cId, collect()) as $ot) {
                    $totalHorasExtra += round(
                        $ot->hours * $ot->base_salary_snapshot * $ot->hourly_rate_factor_snapshot * $ot->overtime_multiplier_snapshot
                    );
                }
            }

            $totalDiario   = $totalTratos + $totalMontoDia;
            $bonosDiarios  = $totalBonusDiario + $totalBonusObjetivo;
            $totalHaberes  = $totalDiario + $bonosDiarios + $totalBonusMensual + $totalHorasExtra;
            $totalNeto     = $totalHaberes - $totalDescuentos;

            if ($totalNeto == 0 && $empYields->isEmpty()) {
                return null;
            }

            $contract = $emp->activeContract ?? $emp->latestContract;

            return [
                'rut'            => $emp->rut,
                'contract_id'    => $contract?->id ?? '—',
                'full_name'      => $emp->full_name,
                'total_diario'   => $totalDiario,
                'bonos_diarios'  => $bonosDiarios,
                'bonus_mensual'  => $totalBonusMensual,
                'horas_extra'    => $totalHorasExtra,
                'total_haberes'  => $totalHaberes,
                'descuentos'     => $totalDescuentos,
                'total_neto'     => $totalNeto,
                'workdays'       => $totalWorkdays,
                'promedio_jh'    => $totalWorkdays > 0 ? round($totalNeto / $totalWorkdays) : 0,
            ];
        })->filter()->values();

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $monthLabel = $monthNames[$monthId - 1] . ' ' . $startDate->year;

        $totals = [
            'total_diario'  => $rows->sum('total_diario'),
            'bonos_diarios' => $rows->sum('bonos_diarios'),
            'bonus_mensual' => $rows->sum('bonus_mensual'),
            'horas_extra'   => $rows->sum('horas_extra'),
            'total_haberes' => $rows->sum('total_haberes'),
            'descuentos'    => $rows->sum('descuentos'),
            'total_neto'    => $rows->sum('total_neto'),
            'workdays'      => $rows->sum('workdays'),
        ];

        $pdf = Pdf::loadView('payroll-reports.sueldos', [
            'rows'       => $rows,
            'monthLabel' => $monthLabel,
            'totals'     => $totals,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("resumen-sueldos-{$month}.pdf");
    }
}
