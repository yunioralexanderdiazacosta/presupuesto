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
use App\Models\MonthlyBonusType;
use App\Models\LaborType;
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

        // ---- RESUMEN LIQUIDACIÓN ----
        // Contratos con actividad en el mes (yields, bonos o descuentos)
        $liqContractIdsFromYields = DailyYield::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('contract_id')
            ->distinct()
            ->pluck('contract_id');

        $liqContractIdsFromBonuses = MonthlyBonus::where('team_id', $user->team_id)
            ->where('month_id', $monthId)
            ->whereNotNull('contract_id')
            ->distinct()
            ->pluck('contract_id');

        $liqContractIdsFromDiscounts = MonthlyDiscount::where('team_id', $user->team_id)
            ->where('month_id', $monthId)
            ->whereNotNull('contract_id')
            ->distinct()
            ->pluck('contract_id');

        $liqContractIds = $liqContractIdsFromYields
            ->merge($liqContractIdsFromBonuses)
            ->merge($liqContractIdsFromDiscounts)
            ->unique()
            ->values();

        $liqContracts = Contract::with(['employee', 'afp', 'healthPlan', 'terminations.causalTermino'])
            ->whereIn('id', $liqContractIds)
            ->get();

        // Labor types para vacaciones y licencias
        $vacacionesTypeIds = LaborType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%vacac%'])
            ->pluck('id');

        $licenciasTypeIds = LaborType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%licenc%'])
            ->pluck('id');

        // Labor types marcados como ausencia (is_absence = true) → no cuentan para proporcional
        $absenceTypeIds = LaborType::where('team_id', $user->team_id)
            ->where('is_absence', true)
            ->pluck('id');

        // Tipo de bono "cargas familiares"
        $cargasBonusTypeIds = MonthlyBonusType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%carga%'])
            ->pluck('id');

        // Tipos de descuento "anticipo"
        $anticiposLiqTypeIds = MonthlyDiscountType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%anticipo%'])
            ->pluck('id');

        // Yields del mes agrupados por contract_id
        $liqYieldsByContract = DailyYield::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('contract_id', $liqContractIds)
            ->get(['contract_id', 'workdays', 'labor_type_id', 'payment_type', 'amount', 'bonus_amount', 'target_price_bonus'])
            ->groupBy('contract_id');

        // Todos los bonos mensuales por contrato (para total haberes)
        $liqAllBonusesByContract = MonthlyBonus::where('team_id', $user->team_id)
            ->whereIn('contract_id', $liqContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'amount'])
            ->groupBy('contract_id');

        // Horas extra por contrato
        $liqOvertimeByContract = OvertimeHour::where('team_id', $user->team_id)
            ->whereIn('contract_id', $liqContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'hours', 'base_salary_snapshot', 'hourly_rate_factor_snapshot', 'overtime_multiplier_snapshot'])
            ->groupBy('contract_id');

        // Cargas familiares por contrato
        $liqCargasByContract = $cargasBonusTypeIds->isNotEmpty()
            ? MonthlyBonus::where('team_id', $user->team_id)
                ->whereIn('contract_id', $liqContractIds)
                ->where('month_id', $monthId)
                ->whereIn('monthly_bonus_type_id', $cargasBonusTypeIds)
                ->get(['contract_id', 'amount'])
                ->groupBy('contract_id')
            : collect();

        // Anticipos por contrato
        $liqAnticiposByContract = $anticiposLiqTypeIds->isNotEmpty()
            ? MonthlyDiscount::where('team_id', $user->team_id)
                ->whereIn('contract_id', $liqContractIds)
                ->where('month_id', $monthId)
                ->whereIn('monthly_discount_type_id', $anticiposLiqTypeIds)
                ->get(['contract_id', 'amount'])
                ->groupBy('contract_id')
            : collect();

        // Otros descuentos (excluye anticipos)
        $otrosDescuentosQuery = MonthlyDiscount::where('team_id', $user->team_id)
            ->whereIn('contract_id', $liqContractIds)
            ->where('month_id', $monthId);
        if ($anticiposLiqTypeIds->isNotEmpty()) {
            $otrosDescuentosQuery->whereNotIn('monthly_discount_type_id', $anticiposLiqTypeIds);
        }
        $liqOtrosByContract = $otrosDescuentosQuery->get(['contract_id', 'amount'])->groupBy('contract_id');

        $liquidacionData = $liqContracts->map(function ($contract) use (
            $liqYieldsByContract, $vacacionesTypeIds, $licenciasTypeIds,
            $liqCargasByContract, $liqAnticiposByContract, $liqOtrosByContract,
            $liqAllBonusesByContract, $liqOvertimeByContract, $absenceTypeIds
        ) {
            $contractYields = $liqYieldsByContract->get($contract->id, collect());
            $totalJornadas  = round((float) $contractYields->sum('workdays'), 2);
            $jhVacaciones   = round((float) $contractYields
                ->filter(fn($y) => $vacacionesTypeIds->contains($y->labor_type_id))
                ->sum('workdays'), 2);
            $licencias      = round((float) $contractYields
                ->filter(fn($y) => $licenciasTypeIds->contains($y->labor_type_id))
                ->sum('workdays'), 2);

            // Jornadas efectivas: excluye labores marcadas como ausencia
            $jornadasEfectivas = round((float) $contractYields
                ->filter(fn($y) => !$absenceTypeIds->contains($y->labor_type_id))
                ->sum('workdays'), 2);

            $sueldoBaseProp = $contract->base_salary > 0
                ? (int) round(($contract->base_salary / 30) * $jornadasEfectivas)
                : 0;

            // Componentes del total haberes
            $totalTratos       = (int) $contractYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia     = (int) $contractYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario  = (int) $contractYields->sum('bonus_amount');
            $totalBonusObjetivo= (int) $contractYields->sum('target_price_bonus');
            $totalBonusMensual = (int) $liqAllBonusesByContract->get($contract->id, collect())->sum('amount');
            $totalHorasExtra   = 0;
            foreach ($liqOvertimeByContract->get($contract->id, collect()) as $ot) {
                $totalHorasExtra += (int) round(
                    $ot->hours * $ot->base_salary_snapshot * $ot->hourly_rate_factor_snapshot * $ot->overtime_multiplier_snapshot
                );
            }
            $totalHaberes = $totalTratos + $totalMontoDia + $totalBonusDiario + $totalBonusObjetivo
                + $totalBonusMensual + $totalHorasExtra;

            $cargasFamiliares = (int) $liqCargasByContract->get($contract->id, collect())->sum('amount');
            $anticipos        = (int) $liqAnticiposByContract->get($contract->id, collect())->sum('amount');
            $otrosDescuentos  = (int) $liqOtrosByContract->get($contract->id, collect())->sum('amount');

            // Finiquito más reciente (si lo tiene)
            $termination = $contract->terminations->sortByDesc('fecha_termino')->first();

            return [
                'contract_id'       => $contract->id,
                'rut'               => $contract->employee?->rut ?? '—',
                'full_name'         => $contract->employee?->full_name ?? '—',
                'contract_date'     => $contract->contract_date?->format('d/m/Y'),
                'contract_type'     => $contract->contract_type,
                'afp'               => $contract->afp?->name ?? '—',
                'health_plan'       => $contract->healthPlan?->name ?? '—',
                'end_date'          => $termination?->fecha_termino?->format('d/m/Y'),
                'causal_termino'    => $termination?->causalTermino?->nombre ?? null,
                'base_salary'       => $contract->base_salary ?? 0,
                'jornadas_efectivas'=> $jornadasEfectivas,
                'sueldo_base_prop'  => $sueldoBaseProp,
                'total_haberes'     => $totalHaberes,
                'total_jornadas'    => $totalJornadas,
                'jh_vacaciones'     => $jhVacaciones,
                'licencias'         => $licencias,
                'cargas_familiares' => $cargasFamiliares,
                'anticipos'         => $anticipos,
                'otros_descuentos'  => $otrosDescuentos,
            ];
        })->sortBy('full_name')->values();

        return Inertia::render('PayrollReports/Index', [
            'employees' => $employeesData,
            'anticipos' => $anticiposData,
            'liquidacion' => $liquidacionData,
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
