<?php

namespace App\Http\Controllers\PayrollReports;

use App\Http\Controllers\Controller;
use App\Models\CompanyReason;
use App\Models\Contract;
use App\Models\DailyYield;
use App\Models\Employee;
use App\Models\MonthlyBonus;
use App\Models\MonthlyDiscount;
use App\Models\MonthlyDiscountType;
use App\Models\OvertimeHour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PayrollReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $user = Auth::user();
        $seasonId = session('season_id');
        $month = $request->get('month', now()->format('Y-m'));
        $monthId = (int) substr($month, 5); // 1-12

        $startDate = Carbon::parse($month . '-01');
        $endDate = $startDate->copy()->endOfMonth();

        // Employees with active contract for this team
        $employees = Employee::with([
                'activeContract.bank', 'activeContract.accountType', 'activeContract.paymentMethod', 'activeContract.companyReason',
                'latestContract.bank', 'latestContract.accountType', 'latestContract.paymentMethod', 'latestContract.companyReason',
            ])
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('paternal_surname')
            ->get();

        $employeeIds = $employees->pluck('id');

        // All daily yields for the month
        $yields = DailyYield::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('employee_id', $employeeIds)
            ->get(['employee_id', 'payment_type', 'amount', 'bonus_amount', 'target_price_bonus', 'workdays']);

        $yieldsByEmployee = $yields->groupBy('employee_id');

        // All contracts for these employees
        $contractsByEmployee = Contract::where('team_id', $user->team_id)
            ->whereIn('employee_id', $employeeIds)
            ->get(['id', 'employee_id'])
            ->groupBy('employee_id');

        $allContractIds = Contract::where('team_id', $user->team_id)
            ->whereIn('employee_id', $employeeIds)
            ->pluck('id');

        // Monthly bonuses for the month
        $bonusesByContract = MonthlyBonus::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'amount'])
            ->groupBy('contract_id');

        // Monthly discounts for the month
        $discountsByContract = MonthlyDiscount::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'amount'])
            ->groupBy('contract_id');

        // Overtime hours for the month
        $overtimesByContract = OvertimeHour::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'hours', 'base_salary_snapshot', 'hourly_rate_factor_snapshot', 'overtime_multiplier_snapshot'])
            ->groupBy('contract_id');

        $employeesData = $employees->map(function ($emp) use (
            $yieldsByEmployee,
            $contractsByEmployee,
            $bonusesByContract,
            $discountsByContract,
            $overtimesByContract
        ) {
            $empYields = $yieldsByEmployee->get($emp->id, collect());
            $empContractIds = $contractsByEmployee->get($emp->id, collect())->pluck('id');

            $totalTratos = $empYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia = $empYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario = $empYields->sum('bonus_amount');
            $totalBonusObjetivo = $empYields->sum('target_price_bonus');
            $totalWorkdays = round((float) $empYields->sum('workdays'), 2);

            $totalBonusMensual = 0;
            $totalDescuentos = 0;
            $totalHorasExtra = 0;

            foreach ($empContractIds as $cId) {
                $totalBonusMensual += $bonusesByContract->get($cId, collect())->sum('amount');
                $totalDescuentos += $discountsByContract->get($cId, collect())->sum('amount');
                foreach ($overtimesByContract->get($cId, collect()) as $ot) {
                    $totalHorasExtra += round(
                        $ot->hours * $ot->base_salary_snapshot * $ot->hourly_rate_factor_snapshot * $ot->overtime_multiplier_snapshot
                    );
                }
            }

            $totalNeto = $totalTratos + $totalMontoDia + $totalBonusDiario + $totalBonusObjetivo
                + $totalBonusMensual + $totalHorasExtra - $totalDescuentos;

            // Only include employees with data
            if ($totalNeto == 0 && $empYields->isEmpty()) {
                return null;
            }

            $contract = $emp->activeContract ?? $emp->latestContract;

            return [
                'id' => $emp->id,
                'full_name' => $emp->full_name,
                'rut' => $emp->rut,
                'position' => $contract?->position ?? '',
                'contract_id' => $contract?->id ?? null,
                'company_reason_id' => $contract?->company_reason_id ?? null,
                'company_reason_name' => $contract?->companyReason?->name ?? '—',
                'bank_name' => $contract?->bank?->name ?? '—',
                'account_type_name' => $contract?->accountType?->name ?? '—',
                'account_number' => $contract?->account_number ?? '—',
                'payment_method_name' => $contract?->paymentMethod?->name ?? '—',
                'total_tratos' => $totalTratos,
                'total_monto_dia' => $totalMontoDia,
                'total_bonus_diario' => $totalBonusDiario,
                'total_bonus_objetivo' => $totalBonusObjetivo,
                'total_bonus_mensual' => $totalBonusMensual,
                'total_horas_extra' => $totalHorasExtra,
                'total_descuentos' => $totalDescuentos,
                'total_workdays' => $totalWorkdays,
                'total_neto' => $totalNeto,
            ];
        })->filter()->values();

        // Anticipos
        $aguinaldoTypeIds = MonthlyDiscountType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%anticipo%'])
            ->pluck('id');

        $anticiposData = collect();
        if ($aguinaldoTypeIds->isNotEmpty()) {
        $anticiposRaw = MonthlyDiscount::with([
                    'contract.employee',
                    'contract.bank',
                    'contract.accountType',
                    'contract.paymentMethod',
                    'contract.companyReason',
                ])
                ->where('team_id', $user->team_id)
                ->whereIn('monthly_discount_type_id', $aguinaldoTypeIds)
                ->where('month_id', $monthId)
                ->get();

            $anticiposData = $anticiposRaw->map(fn($d) => [
                'contract_id'         => $d->contract_id,
                'company_reason_id'   => $d->contract?->company_reason_id ?? null,
                'company_reason_name' => $d->contract?->companyReason?->name ?? '—',
                'rut'                 => $d->contract?->employee?->rut ?? '—',
                'full_name'           => $d->contract?->employee?->full_name ?? '—',
                'bank_name'           => $d->contract?->bank?->name ?? '—',
                'account_type_name'   => $d->contract?->accountType?->name ?? '—',
                'account_number'      => $d->contract?->account_number ?? '—',
                'payment_method_name' => $d->contract?->paymentMethod?->name ?? '—',
                'amount'              => $d->amount,
                'observations'        => $d->observations ?? '',
            ])->values();
        }

        // Razones sociales usadas por los contratos del equipo
        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($cr) => ['value' => $cr->id, 'label' => $cr->name]);

        return Inertia::render('PayrollReports/Index', [
            'employees' => $employeesData,
            'anticipos' => $anticiposData,
            'companyReasons' => $companyReasons,
            'month' => $month,
            'totals' => [
                'tratos' => $employeesData->sum('total_tratos'),
                'monto_dia' => $employeesData->sum('total_monto_dia'),
                'bonus_diario' => $employeesData->sum('total_bonus_diario'),
                'bonus_objetivo' => $employeesData->sum('total_bonus_objetivo'),
                'bonus_mensual' => $employeesData->sum('total_bonus_mensual'),
                'horas_extra' => $employeesData->sum('total_horas_extra'),
                'descuentos' => $employeesData->sum('total_descuentos'),
                'neto' => $employeesData->sum('total_neto'),
            ],
        ]);
    }
}
