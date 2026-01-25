<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use Inertia\Inertia;

class InvoicePaymentDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        // Total pagado en la temporada
        $totalPagado = InvoicePayment::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->sum('amount');

        // Total de facturas
        $totalFacturas = Invoice::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->count();

        // Calcular saldo pendiente total
        $invoicesWithTotals = Invoice::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->with(['invoiceProducts', 'payments'])
            ->get()
            ->map(function($invoice) {
                $total = $invoice->invoiceProducts->sum(function($ip) {
                    return $ip->unit_price * $ip->amount;
                });
                $paid = $invoice->payments->sum('amount');
                return [
                    'total' => $total,
                    'paid' => $paid,
                    'balance' => $total - $paid
                ];
            });

        $totalInvoices = $invoicesWithTotals->sum('total');
        $totalPagadoCalc = $invoicesWithTotals->sum('paid');
        $saldoPendiente = $invoicesWithTotals->sum('balance');

        // Facturas por estado de pago
        $facturasPendientes = $invoicesWithTotals->filter(fn($i) => $i['paid'] == 0)->count();
        $facturasParciales = $invoicesWithTotals->filter(fn($i) => $i['paid'] > 0 && $i['paid'] < $i['total'])->count();
        $facturasPagadas = $invoicesWithTotals->filter(fn($i) => $i['paid'] >= $i['total'])->count();

        // Pagos por método
        $pagosPorMetodo = InvoicePayment::select('payment_method', DB::raw('SUM(amount) as total'))
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->groupBy('payment_method')
            ->get()
            ->map(function($item) {
                $methods = [
                    1 => 'Transferencia',
                    2 => 'Efectivo',
                    3 => 'Cheque'
                ];
                return [
                    'method' => $methods[$item->payment_method] ?? 'Desconocido',
                    'total' => $item->total
                ];
            });

        // Pagos por banco (solo transferencias)
        $pagosPorBanco = InvoicePayment::select('bank_id', DB::raw('SUM(amount) as total'))
            ->with('bank')
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->where('payment_method', 1) // Solo transferencias
            ->whereNotNull('bank_id')
            ->groupBy('bank_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'bank' => $item->bank ? $item->bank->name : 'Sin banco',
                    'total' => $item->total
                ];
            });

        // Pagos por mes
        $pagosPorMes = InvoicePayment::select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                $months = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];
                return [
                    'month' => $months[$item->month] . ' ' . $item->year,
                    'total' => $item->total
                ];
            });

        // Top 10 proveedores con más pagos
        $topProveedores = InvoicePayment::select('suppliers.name as supplier_name', DB::raw('SUM(invoice_payments.amount) as total'))
            ->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->join('suppliers', 'invoices.supplier_id', '=', 'suppliers.id')
            ->where('invoice_payments.team_id', $user->team_id)
            ->where('invoice_payments.season_id', $season_id)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'supplier' => $item->supplier_name ?? 'Sin proveedor',
                    'total' => $item->total
                ];
            });

        // Pagos recientes (últimos 10)
        $pagosRecientes = InvoicePayment::with(['invoice.supplier', 'user'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('payment_date')
            ->limit(10)
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'date' => $payment->payment_date->format('d-m-Y'),
                    'invoice' => $payment->invoice->number_document,
                    'supplier' => $payment->invoice->supplier->name ?? '',
                    'amount' => $payment->amount,
                    'method' => $payment->payment_method_name,
                    'user' => $payment->user->name ?? ''
                ];
            });

        return Inertia::render('InvoicePayments/Dashboard', [
            'stats' => [
                'total_pagado' => $totalPagado,
                'total_facturas_monto' => $totalInvoices,
                'saldo_pendiente' => $saldoPendiente,
                'total_facturas_count' => $totalFacturas,
                'facturas_pendientes' => $facturasPendientes,
                'facturas_parciales' => $facturasParciales,
                'facturas_pagadas' => $facturasPagadas,
            ],
            'pagos_por_metodo' => $pagosPorMetodo,
            'pagos_por_banco' => $pagosPorBanco,
            'pagos_por_mes' => $pagosPorMes,
            'top_proveedores' => $topProveedores,
            'pagos_recientes' => $pagosRecientes,
        ]);
    }
}
