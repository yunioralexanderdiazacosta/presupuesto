<?php

namespace App\Http\Controllers\PayrollReports;

use App\Http\Controllers\Controller;
use App\Models\MonthlyDiscount;
use App\Models\MonthlyDiscountType;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportPayrollAnticiposPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $user = Auth::user();
        $month = $request->month;
        $monthId = (int) substr($month, 5);

        $aguinaldoTypeIds = MonthlyDiscountType::where('team_id', $user->team_id)
            ->whereRaw('LOWER(name) LIKE ?', ['%anticipo%'])
            ->pluck('id');

        $rows = collect();

        if ($aguinaldoTypeIds->isNotEmpty()) {
            $rows = MonthlyDiscount::with([
                    'contract.employee',
                    'contract.bank',
                    'contract.accountType',
                    'contract.paymentMethod',
                ])
                ->where('team_id', $user->team_id)
                ->whereIn('monthly_discount_type_id', $aguinaldoTypeIds)
                ->where('month_id', $monthId)
                ->get()
                ->map(fn($d) => [
                    'contract_id'         => $d->contract_id,
                    'rut'                 => $d->contract?->employee?->rut ?? '—',
                    'full_name'           => $d->contract?->employee?->full_name ?? '—',
                    'bank_name'           => $d->contract?->bank?->name ?? '—',
                    'account_type_name'   => $d->contract?->accountType?->name ?? '—',
                    'account_number'      => $d->contract?->account_number ?? '—',
                    'payment_method_name' => $d->contract?->paymentMethod?->name ?? '—',
                    'amount'              => $d->amount,
                    'observations'        => $d->observations ?? '',
                ])
                ->values();
        }

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $monthLabel = $monthNames[$monthId - 1] . ' ' . Carbon::parse($month . '-01')->year;
        $grandTotal = $rows->sum('amount');

        $pdf = Pdf::loadView('payroll-reports.anticipos', [
            'rows'       => $rows,
            'monthLabel' => $monthLabel,
            'grandTotal' => $grandTotal,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("anticipos-{$month}.pdf");
    }
}
