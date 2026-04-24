<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Vacation;
use App\Models\VacationEntitlement;
use Carbon\Carbon;

class VacationCalculatorService
{
    /**
     * Calcula los días hábiles entre dos fechas (ambas inclusive),
     * excluyendo sábados, domingos y feriados del equipo + nacionales.
     */
    public function countBusinessDays(Carbon $start, Carbon $end, int $teamId): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        // Obtener todos los feriados en el rango (nacionales + del equipo)
        $holidays = Holiday::where(function ($q) use ($teamId) {
            $q->whereNull('team_id')->orWhere('team_id', $teamId);
        })
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $count   = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            // Lunes a viernes, que no sea feriado
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Calcula el balance de vacaciones de un empleado.
     * Retorna array con todos los datos del cálculo.
     */
    public function calculateBalance(Employee $employee, int $teamId): array
    {
        // Query directa para evitar el bug de latestOfMany() en MySQL
        $contract = Contract::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->latest('contract_date')
            ->first();

        if (!$contract) {
            return $this->emptyBalance('Sin contrato activo');
        }

        if ($contract->contract_type !== 'Indefinido') {
            return $this->emptyBalance('Contrato no es Indefinido');
        }

        $contractDate = Carbon::parse($contract->contract_date);
        $today        = Carbon::today();

        // Meses trabajados completos (para cálculo legal)
        $monthsWorked = $contractDate->diffInMonths($today);

        // Meses con decimal (informativo, para mostrar en UI)
        $monthsWorkedDecimal = round($contractDate->diffInDays($today) / 30.44, 1);

        // Años en empresa (para progresivas)
        $yearsInCompany = $contractDate->diffInYears($today);

        // Años anteriores reconocidos
        $entitlement    = VacationEntitlement::where('employee_id', $employee->id)->first();
        $previousYears  = $entitlement?->anos_anteriores ?? 0;
        $totalYears     = $yearsInCompany + $previousYears;

        // Tasa base según años totales (progresivas)
        $ratePerYear = 15;
        if ($totalYears >= 10) {
            $extraDays   = floor(($totalYears - 10) / 3);
            $ratePerYear = 15 + $extraDays;
        }

        // Días ganados = meses × (tasa / 12)
        $ratePerMonth = $ratePerYear / 12;   // = 1.25 en el caso base
        $daysEarned   = $monthsWorked * $ratePerMonth;

        // Días tomados registrados BAJO ESTE CONTRATO (no de contratos anteriores)
        $daysTaken = Vacation::where('contract_id', $contract->id)
            ->where('team_id', $teamId)
            ->sum('dias_habiles');

        $balance = $daysEarned - $daysTaken;

        return [
            'months_worked'         => $monthsWorked,
            'months_worked_decimal' => $monthsWorkedDecimal,
            'years_in_company'      => $yearsInCompany,
            'total_years'     => $totalYears,
            'rate_per_year'   => $ratePerYear,
            'rate_per_month'  => round($ratePerMonth, 4),
            'days_earned'     => round($daysEarned, 2),
            'days_taken'      => (int) $daysTaken,
            'balance'         => round($balance, 2),
            'eligible'        => true,
            'contract_date'   => $contractDate->format('Y-m-d'),
        ];
    }

    private function emptyBalance(string $reason): array
    {
        return [
            'months_worked'   => 0,
            'years_in_company'=> 0,
            'total_years'     => 0,
            'rate_per_year'   => 15,
            'rate_per_month'  => 1.25,
            'days_earned'     => 0,
            'days_taken'      => 0,
            'balance'         => 0,
            'eligible'        => false,
            'reason'          => $reason,
            'contract_date'   => null,
        ];
    }
}
