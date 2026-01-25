<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Auth;

class DeleteInvoicePaymentController extends Controller
{
    public function __invoke(InvoicePayment $payment)
    {
        $user = Auth::user();

        // Verificar que el pago pertenece al equipo del usuario
        if ($payment->team_id !== $user->team_id) {
            abort(403, 'No tiene permisos para eliminar este pago.');
        }

        $payment->delete();

        return redirect()->route('invoice-payments.index')->with('success', 'Pago eliminado exitosamente.');
    }
}
