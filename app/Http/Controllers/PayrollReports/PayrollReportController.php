<?php

namespace App\Http\Controllers\PayrollReports;

use App\Http\Controllers\Controller;
use App\Models\CompanyReason;
use App\Models\Contract;
use App\Models\DailyYield;
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

        // Contratos con actividad en el mes (yields, bonos, descuentos, HE)
        $contractIdsFromYields = DailyYield::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('contract_id')
            ->distinct()->pluck('contract_id');

        $contractIdsFromBonuses = MonthlyBonus::where('team_id', $user->team_id)
            ->where('month_id', $monthId)->whereNotNull('contract_id')
            ->distinct()->pluck('contract_id');

        $contractIdsFromDiscounts = MonthlyDiscount::where('team_id', $user->team_id)
            ->where('month_id', $monthId)->whereNotNull('contract_id')
            ->distinct()->pluck('contract_id');

        $contractIdsFromOvertime = OvertimeHour::where('team_id', $user->team_id)
            ->where('month_id', $monthId)->whereNotNull('contract_id')
            ->distinct()->pluck('contract_id');

        $allActiveContractIds = $contractIdsFromYields
            ->merge($contractIdsFromBonuses)
            ->merge($contractIdsFromDiscounts)
            ->merge($contractIdsFromOvertime)
            ->unique()->values();

        // Cargar contratos con todas las relaciones necesarias
        $allContracts = Contract::with([
            'employee',
            'afp', 'healthPlan',
            'bank', 'accountType', 'paymentMethod', 'companyReason',
            'terminations.causalTermino',
        ])->whereIn('id', $allActiveContractIds)->get();

        // Labor types
        $vacacionesTypeIds = LaborType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%vacac%'])->pluck('id');
        $licenciasTypeIds = LaborType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%licenc%'])->pluck('id');
        $absenceTypeIds = LaborType::where('team_id', $user->team_id)
            ->where('is_absence', true)->pluck('id');

        // Tipos de bono/descuento especiales
        $cargasBonusTypeIds = MonthlyBonusType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%carga%'])->pluck('id');
        $anticiposTypeIds = MonthlyDiscountType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%anticipo%'])->pluck('id');

        // Yields del mes por contract_id
        $yieldsByContract = DailyYield::where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereIn('contract_id', $allActiveContractIds)
            ->get(['contract_id', 'payment_type', 'amount', 'bonus_amount', 'target_price_bonus', 'workdays', 'labor_type_id'])
            ->groupBy('contract_id');

        // Bonos mensuales por contract_id
        $allBonusesByContract = MonthlyBonus::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allActiveContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'monthly_bonus_type_id', 'amount'])
            ->groupBy('contract_id');

        // Descuentos mensuales por contract_id
        $allDiscountsByContract = MonthlyDiscount::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allActiveContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'monthly_discount_type_id', 'amount'])
            ->groupBy('contract_id');

        // Horas extra por contract_id
        $overtimesByContract = OvertimeHour::where('team_id', $user->team_id)
            ->whereIn('contract_id', $allActiveContractIds)
            ->where('month_id', $monthId)
            ->get(['contract_id', 'hours', 'base_salary_snapshot', 'hourly_rate_factor_snapshot', 'overtime_multiplier_snapshot'])
            ->groupBy('contract_id');

        // Loop unificado: un row por contrato, alimenta TODOS los tabs
        $allContractData = $allContracts->map(function ($contract) use (
            $yieldsByContract, $absenceTypeIds, $vacacionesTypeIds, $licenciasTypeIds,
            $allBonusesByContract, $cargasBonusTypeIds,
            $allDiscountsByContract, $anticiposTypeIds,
            $overtimesByContract
        ) {
            $contractYields = $yieldsByContract->get($contract->id, collect());

            $totalTratos        = (int) $contractYields->where('payment_type', 'trato')->sum('amount');
            $totalMontoDia      = (int) $contractYields->where('payment_type', 'dia')->sum('amount');
            $totalBonusDiario   = (int) $contractYields->sum('bonus_amount');
            $totalBonusObjetivo = (int) $contractYields->sum('target_price_bonus');
            $totalWorkdays      = round((float) $contractYields->sum('workdays'), 2);

            $jornadasEfectivas = round((float) $contractYields
                ->filter(fn($y) => !$absenceTypeIds->contains($y->labor_type_id))
                ->sum('workdays'), 2);
            $jhVacaciones = round((float) $contractYields
                ->filter(fn($y) => $vacacionesTypeIds->contains($y->labor_type_id))
                ->sum('workdays'), 2);
            $licencias = round((float) $contractYields
                ->filter(fn($y) => $licenciasTypeIds->contains($y->labor_type_id))
                ->sum('workdays'), 2);

            $contractBonuses      = $allBonusesByContract->get($contract->id, collect());
            $totalBonusMensual    = (int) $contractBonuses->sum('amount');
            $cargasFamiliares     = $cargasBonusTypeIds->isNotEmpty()
                ? (int) $contractBonuses->whereIn('monthly_bonus_type_id', $cargasBonusTypeIds->toArray())->sum('amount')
                : 0;

            $contractDiscounts = $allDiscountsByContract->get($contract->id, collect());
            $totalDescuentos   = (int) $contractDiscounts->sum('amount');
            $anticipos         = $anticiposTypeIds->isNotEmpty()
                ? (int) $contractDiscounts->whereIn('monthly_discount_type_id', $anticiposTypeIds->toArray())->sum('amount')
                : 0;
            $otrosDescuentos   = $anticiposTypeIds->isNotEmpty()
                ? (int) $contractDiscounts->whereNotIn('monthly_discount_type_id', $anticiposTypeIds->toArray())->sum('amount')
                : $totalDescuentos;

            $totalHorasExtra = 0;
            foreach ($overtimesByContract->get($contract->id, collect()) as $ot) {
                $totalHorasExtra += (int) round(
                    $ot->hours * $ot->base_salary_snapshot * $ot->hourly_rate_factor_snapshot * $ot->overtime_multiplier_snapshot
                );
            }

            $totalHaberes = $totalTratos + $totalMontoDia + $totalBonusDiario + $totalBonusObjetivo
                + $totalBonusMensual + $totalHorasExtra;
            $totalNeto = $totalHaberes - $totalDescuentos;

            $sueldoBaseProp = $contract->base_salary > 0
                ? (int) round(($contract->base_salary / 30) * $jornadasEfectivas)
                : 0;

            if ($totalNeto == 0 && $contractYields->isEmpty() && $totalBonusMensual == 0 && $totalDescuentos == 0) {
                return null;
            }

            $termination = $contract->terminations->sortByDesc('fecha_termino')->first();

            return [
                'id'                  => $contract->id,
                'employee_id'         => $contract->employee_id,
                'contract_id'         => $contract->id,
                'full_name'           => $contract->employee?->full_name ?? '—',
                'rut'                 => $contract->employee?->rut ?? '—',
                'position'            => $contract->position ?? '',
                'company_reason_id'   => $contract->company_reason_id ?? null,
                'company_reason_name' => $contract->companyReason?->name ?? '—',
                'bank_name'           => $contract->bank?->name ?? '—',
                'account_type_name'   => $contract->accountType?->name ?? '—',
                'account_number'      => $contract->account_number ?? '—',
                'payment_method_name' => $contract->paymentMethod?->name ?? '—',
                'total_tratos'        => $totalTratos,
                'total_monto_dia'     => $totalMontoDia,
                'total_bonus_diario'  => $totalBonusDiario,
                'total_bonus_objetivo'=> $totalBonusObjetivo,
                'total_bonus_mensual' => $totalBonusMensual,
                'total_horas_extra'   => $totalHorasExtra,
                'total_haberes'       => $totalHaberes,
                'total_descuentos'    => $totalDescuentos,
                'total_workdays'      => $totalWorkdays,
                'total_neto'          => $totalNeto,
                // Campos liquidación
                'contract_date'       => $contract->contract_date?->format('d/m/Y'),
                'contract_type'       => $contract->contract_type,
                'afp'                 => $contract->afp?->name ?? '—',
                'health_plan'         => $contract->healthPlan?->name ?? '—',
                'end_date'            => $termination?->fecha_termino?->format('d/m/Y'),
                'causal_termino'      => $termination?->causalTermino?->nombre ?? null,
                'base_salary'         => $contract->base_salary ?? 0,
                'jornadas_efectivas'  => $jornadasEfectivas,
                'sueldo_base_prop'    => $sueldoBaseProp,
                'total_jornadas'      => $totalWorkdays,
                'jh_vacaciones'       => $jhVacaciones,
                'licencias'           => $licencias,
                'cargas_familiares'   => $cargasFamiliares,
                'anticipos'           => $anticipos,
                'otros_descuentos'    => $otrosDescuentos,
            ];
        })->filter()->sortBy('full_name')->values();

        $employeesData  = $allContractData;
        $liquidacionData = $allContractData;

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

        // Razones sociales
        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($cr) => ['value' => $cr->id, 'label' => $cr->name]);

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
