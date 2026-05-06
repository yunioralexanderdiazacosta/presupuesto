<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoicePayments\UpdateInvoicePaymentRequest;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateInvoicePaymentController extends Controller
{
    public function __invoke(UpdateInvoicePaymentRequest $request, InvoicePayment $payment)
    {
        $user = Auth::user();

        // Verificar que el pago pertenece al equipo del usuario
        if ($payment->team_id !== $user->team_id) {
            abort(403, 'No tiene permisos para editar este pago.');
        }

        // Calcular total real (neto + IVA si aplica)
        $invoice      = $payment->invoice()->with(['typeDocument', 'invoiceProducts'])->first();
        $totalNeto    = $invoice->invoiceProducts->sum(fn($ip) => $ip->unit_price * $ip->amount);
        $tipoDoc      = strtoupper($invoice->typeDocument?->name ?? '');
        $hasIva       = in_array($tipoDoc, ['FACTURA', 'NOTA CREDITO', 'NOTA DEBITO']);
        $totalInvoice = $totalNeto + ($hasIva ? round($totalNeto * 0.19) : 0);
        $totalPaid    = $invoice->payments()->where('id', '!=', $payment->id)->sum('amount');
        $balance      = $totalInvoice - $totalPaid;

        if ($request->amount > $balance) {
            return back()->withErrors(['amount' => 'El monto no puede exceder el saldo pendiente de '.number_format($balance, 2)]);
        }

        $payment->update([
            'bank_id' => $request->bank_id,
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_number' => $request->transaction_number,
            'observations' => $request->observations,
        ]);

        return redirect()->route('invoice-payments.index')->with('success', 'Pago actualizado exitosamente.');
    }
}
