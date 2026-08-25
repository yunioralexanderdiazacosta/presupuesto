<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\Month;
use Carbon\Carbon;

class InvoiceDebtReportController extends Controller
{
    /**
     * Detalle de deuda (saldo pendiente + parcial) por factura, para el informe
     * "Deuda por Razón Social" (modal en InvoicePayments/Index.vue).
     * Aplica los mismos filtros que el listado principal para que los montos coincidan.
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return response()->json(['error' => 'Debe seleccionar una campaña activa.'], 422);
        }

        $term        = $request->term ?? '';
        $dateFrom    = $request->date_from ?? '';
        $dateTo      = $request->date_to ?? '';
        $supplierId  = $request->supplier_id ?? '';
        $paymentType = $request->has('payment_type') ? $request->payment_type : '1'; // Default: Crédito

        $invoices = Invoice::with([
                'invoiceProducts',
                'payments',
                'typeDocument',
                'companyReason',
                'supplier:id,name',
                'month:id,name',
                'creditDebitNotes.items',
            ])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($term, function ($q, $search) {
                $q->where(function($q2) use ($search) {
                    $q2->where('number_document', 'like', '%'.$search.'%')
                       ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($dateFrom, fn($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($dateTo,   fn($q, $date) => $q->whereDate('date', '<=', $date))
            ->when($supplierId, fn($q, $id) => $q->where('supplier_id', $id))
            ->when($paymentType !== '', fn($q) => $q->where('payment_type', $paymentType))
            ->get()
            ->map(function ($invoice) {
                $debt = $invoice->calculateDebt();
                if ($debt['is_annulled'] || $debt['balance'] <= 0) {
                    return null;
                }

                // Días de atraso: positivo = vencida hace N días, 0 o negativo = aún no vence.
                $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
                $daysOverdue = intdiv(now()->startOfDay()->timestamp - $dueDate->timestamp, 86400);
                $agingBucket = match (true) {
                    $daysOverdue <= 0  => 'current',
                    $daysOverdue <= 30 => '1-30',
                    $daysOverdue <= 60 => '31-60',
                    $daysOverdue <= 90 => '61-90',
                    default            => '90+',
                };

                return [
                    'id'                  => $invoice->id,
                    'number_document'     => $invoice->number_document ?: $invoice->number,
                    'date'                => $invoice->date ? Carbon::parse($invoice->date)->format('Y-m-d') : null,
                    'due_date'            => $dueDate->format('Y-m-d'),
                    'company_reason_id'   => $invoice->company_reason_id,
                    'company_reason_name' => $invoice->companyReason?->name ?? 'Sin razón social',
                    'month_id'            => $invoice->month_id,
                    'month_name'          => $invoice->month?->name ?? 'Sin mes',
                    'supplier_id'         => $invoice->supplier_id,
                    'supplier_name'       => $invoice->supplier?->name ?? 'Sin proveedor',
                    'balance'             => $debt['balance'],
                    'days_overdue'        => $daysOverdue,
                    'aging_bucket'        => $agingBucket,
                ];
            })
            ->filter()
            ->values();

        $companyReasons = $invoices->pluck('company_reason_name', 'company_reason_id')
            ->unique()
            ->map(fn($name, $id) => ['value' => $id, 'label' => $name])
            ->sortBy('label')
            ->values();

        $months = $invoices->pluck('month_name', 'month_id')
            ->unique()
            ->map(fn($name, $id) => ['value' => $id, 'label' => $name])
            ->sortBy('value')
            ->values();

        $suppliers = $invoices->pluck('supplier_name', 'supplier_id')
            ->unique()
            ->map(fn($name, $id) => ['value' => $id, 'label' => $name])
            ->sortBy('label')
            ->values();

        // Listado completo de meses (1-12), para la opción "Ver todos los meses" en el informe.
        $allMonths = Month::orderBy('id')->get(['id', 'name'])
            ->map(fn($m) => ['id' => $m->id, 'name' => $m->name]);

        return response()->json([
            'invoices'        => $invoices,
            'company_reasons' => $companyReasons,
            'months'          => $months,
            'suppliers'       => $suppliers,
            'all_months'      => $allMonths,
        ]);
    }
}
