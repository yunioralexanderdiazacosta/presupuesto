<?php

namespace App\Http\Controllers\PaymentRequests;

use App\Http\Controllers\Controller;
use App\Mail\PaymentRequestResolved;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ResolvePaymentRequestController extends Controller
{
    public function __invoke(PaymentRequest $paymentRequest, User $user)
    {
        // El enlace firmado ya identifica al destinatario que gestiona el pago
        if ($paymentRequest->status !== 'pendiente') {
            return view('payment-requests.already-resolved', [
                'paymentRequest' => $paymentRequest,
            ]);
        }

        $paymentRequest->update([
            'status' => 'gestionada',
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);

        $paymentRequest->load('user');
        if ($paymentRequest->user && $paymentRequest->user->email) {
            Mail::to($paymentRequest->user->email)
                ->send(new PaymentRequestResolved($paymentRequest, $user->name));
        }

        return view('payment-requests.resolved-confirmation', [
            'paymentRequest' => $paymentRequest,
            'resolvedByName' => $user->name,
        ]);
    }
}
