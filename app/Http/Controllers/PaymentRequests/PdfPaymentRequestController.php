<?php

namespace App\Http\Controllers\PaymentRequests;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PdfPaymentRequestController extends Controller
{
    public function __invoke(PaymentRequest $paymentRequest)
    {
        $user = Auth::user();

        if ($paymentRequest->team_id !== $user->team_id) {
            abort(403);
        }

        $paymentRequest->load(['team:id,name', 'user:id,name', 'resolvedBy:id,name', 'costCenters:id,name', 'recipients:id,name', 'files']);

        $pdf = Pdf::loadView('pdfs.payment-request', [
            'paymentRequest' => $paymentRequest,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('solicitud-' . $paymentRequest->number . '.pdf');
    }
}
