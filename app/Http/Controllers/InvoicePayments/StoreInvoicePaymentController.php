<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoicePayments\StoreInvoicePaymentRequest;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class StoreInvoicePaymentController extends Controller
{
    public function __invoke(StoreInvoicePaymentRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Validar que la factura existe y pertenece al equipo
        $invoice = Invoice::where('id', $request->invoice_id)
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->firstOrFail();

        // Validar saldo pendiente
        $totalInvoice = $invoice->invoiceProducts()->sum(\DB::raw('unit_price * amount'));
        $totalPaid = $invoice->payments()->sum('amount');
        $balance = $totalInvoice - $totalPaid;

        if ($request->amount > $balance) {
            return back()->withErrors(['amount' => 'El monto no puede exceder el saldo pendiente de '.number_format($balance, 2)]);
        }

        $payment = InvoicePayment::create([
            'invoice_id' => $request->invoice_id,
            'team_id' => $user->team_id,
            'season_id' => $season_id,
            'user_id' => $user->id,
            'bank_id' => $request->bank_id,
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_number' => $request->transaction_number,
            'observations' => $request->observations,
        ]);

        return redirect()->route('invoice-payments.index')->with('success', 'Pago registrado exitosamente.');
    }
}
