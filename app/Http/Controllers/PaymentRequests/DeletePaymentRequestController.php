<?php

namespace App\Http\Controllers\PaymentRequests;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DeletePaymentRequestController extends Controller
{
    public function __invoke(PaymentRequest $paymentRequest)
    {
        $user = Auth::user();

        if ($paymentRequest->team_id !== $user->team_id) {
            abort(403);
        }

        if ($paymentRequest->status !== 'pendiente') {
            return back()->withErrors(['status' => 'Solo se pueden eliminar solicitudes pendientes.']);
        }

        foreach ($paymentRequest->files as $file) {
            Storage::disk('public')->delete($file->file_path);
        }

        $number = $paymentRequest->number;
        $paymentRequest->delete();

        return redirect()->route('payment-requests.index')
            ->with('success', "Solicitud {$number} eliminada.");
    }
}
