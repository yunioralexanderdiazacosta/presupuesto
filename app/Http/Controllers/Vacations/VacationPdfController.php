<?php

namespace App\Http\Controllers\Vacations;

use App\Http\Controllers\Controller;
use App\Models\CompanyReason;
use App\Models\Contract;
use App\Models\Vacation;
use App\Services\VacationCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VacationPdfController extends Controller
{
    public function __invoke(Request $request, Vacation $vacation)
    {
        $user = Auth::user();
        abort_if($vacation->team_id !== $user->team_id, 403);

        $employee = $vacation->employee;
        $contract = Contract::where('id', $vacation->contract_id)->first();

        // Ciudad de la razón social del team
        $companyReason = CompanyReason::where('team_id', $user->team_id)->first();
        $lugar = $companyReason?->city ?? '';

        // Fecha del documento: hoy (cuando se imprime)
        $today = Carbon::today();

        $fechaInicio = Carbon::parse($vacation->fecha_inicio);
        $fechaFin    = Carbon::parse($vacation->fecha_fin);

        // Período de vacaciones (desde aniversario de contrato)
        $periodStart = null;
        $periodEnd   = null;
        if ($contract) {
            $contractDate = Carbon::parse($contract->contract_date);
            $anniversary  = $contractDate->copy()->setYear($fechaInicio->year);
            if ($anniversary->gt($fechaInicio)) {
                $anniversary->subYear();
            }
            $periodStart = $anniversary->year;
            $periodEnd   = $periodStart + 1;
        }

        // Días hábiles vs inhábiles
        $totalDias     = $fechaInicio->diffInDays($fechaFin) + 1;
        $diasHabiles   = $vacation->dias_habiles;
        $diasInhabiles = $totalDias - $diasHabiles;

        // Saldo actual y días progresivos
        $service         = new VacationCalculatorService();
        $balance         = $service->calculateBalance($employee, $user->team_id);
        $diasProgresivos = max(0, ($balance['rate_per_year'] ?? 15) - 15);
        $saldoPendiente  = round($balance['balance'] ?? 0, 1);

        // Parcial o total
        $tipoPeriodo = $saldoPendiente <= 0 ? 'total' : 'parcial';

        $teamName = $user->currentTeam->name ?? '';

        $meses = [
            1  => 'Enero',    2  => 'Febrero',   3  => 'Marzo',
            4  => 'Abril',    5  => 'Mayo',       6  => 'Junio',
            7  => 'Julio',    8  => 'Agosto',     9  => 'Septiembre',
            10 => 'Octubre',  11 => 'Noviembre',  12 => 'Diciembre',
        ];

        $pdf = Pdf::loadView('pdfs.vacation-voucher', compact(
            'vacation', 'employee', 'contract',
            'lugar', 'today', 'meses',
            'periodStart', 'periodEnd',
            'fechaInicio', 'fechaFin',
            'diasHabiles', 'diasInhabiles',
            'diasProgresivos', 'saldoPendiente',
            'tipoPeriodo', 'teamName'
        ));

        $pdf->setPaper('letter', 'portrait');

        $filename = 'comprobante-vacaciones-' . $employee->paternal_surname . '-' . $today->format('Y-m-d') . '.pdf';

        return $pdf->stream($filename);
    }
}
