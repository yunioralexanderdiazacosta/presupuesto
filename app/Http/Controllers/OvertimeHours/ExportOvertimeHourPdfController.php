<?php

namespace App\Http\Controllers\OvertimeHours;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\OvertimeHour;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportOvertimeHourPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $contractId = $request->get('contract_id'); // null = todos
        $monthId    = $request->get('month_id');    // null = todos

        $query = OvertimeHour::with([
            'contract.employee',
            'month',
            'laborType',
            'overtimeType',
        ])
            ->where('team_id', $user->team_id)
            ->orderBy('contract_id')
            ->orderBy('month_id');

        if ($contractId) {
            $query->where('contract_id', $contractId);
        }

        if ($monthId) {
            $query->where('month_id', $monthId);
        }

        $records = $query->get();

        // Agrupar por trabajador
        $byWorker = $records->groupBy(fn($r) => $r->contract_id);

        $workers = $byWorker->map(function ($items) {
            $first = $items->first();
            return [
                'name'     => $first->contract->employee->full_name ?? '—',
                'rut'      => $first->contract->employee->rut ?? '—',
                'lines'    => $items->map(fn($r) => [
                    'month'         => $r->month->name ?? '—',
                    'overtime_type' => $r->overtimeType->name ?? '—',
                    'labor_type'    => $r->laborType->name ?? '—',
                    'hours'         => $r->hours,
                    'amount'        => $r->hours * $r->base_salary_snapshot * $r->hourly_rate_factor_snapshot * $r->overtime_multiplier_snapshot,
                ])->values(),
                'total_hours'  => $items->sum('hours'),
                'total_amount' => $items->sum(fn($r) => $r->hours * $r->base_salary_snapshot * $r->hourly_rate_factor_snapshot * $r->overtime_multiplier_snapshot),
            ];
        })->values();

        // Etiqueta mes para el título
        $monthLabel = $monthId
            ? (OvertimeHour::with('month')->where('team_id', $user->team_id)->where('month_id', $monthId)->first()?->month->name ?? "Mes #{$monthId}")
            : 'Todos los meses';

        $totalHours  = $records->sum('hours');
        $totalAmount = $records->sum(fn($r) => $r->hours * $r->base_salary_snapshot * $r->hourly_rate_factor_snapshot * $r->overtime_multiplier_snapshot);

        $pdf = Pdf::loadView('payroll-reports.overtime-hours', [
            'workers'     => $workers,
            'monthLabel'  => $monthLabel,
            'totalHours'  => $totalHours,
            'totalAmount' => $totalAmount,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("horas-extras-{$monthId}.pdf");
    }
}
