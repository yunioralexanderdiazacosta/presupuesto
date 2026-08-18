<?php

namespace App\Http\Controllers\PaymentRequests;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowPaymentRequestController extends Controller
{
    public function __invoke(PaymentRequest $paymentRequest)
    {
        $user = Auth::user();

        if ($paymentRequest->team_id !== $user->team_id) {
            abort(403);
        }

        $paymentRequest->load(['user:id,name', 'resolvedBy:id,name', 'costCenters:id,name', 'recipients:id,name', 'files']);

        $request = [
            'id' => $paymentRequest->id,
            'number' => $paymentRequest->number,
            'date_formatted' => $paymentRequest->date->format('d/m/Y'),
            'character' => $paymentRequest->character,
            'character_label' => $paymentRequest->character_label,
            'character_color' => $paymentRequest->character_color,
            'concept_observations' => $paymentRequest->concept_observations,
            'files' => $paymentRequest->files->map(fn($f) => ['id' => $f->id, 'file_path' => $f->file_path, 'original_name' => $f->original_name])->values(),
            'status' => $paymentRequest->status,
            'status_label' => $paymentRequest->status_label,
            'status_color' => $paymentRequest->status_color,
            'user_name' => $paymentRequest->user->name ?? '',
            'user_id' => $paymentRequest->user_id,
            'resolved_by_name' => $paymentRequest->resolvedBy->name ?? null,
            'resolved_at' => $paymentRequest->resolved_at?->format('d/m/Y H:i'),
            'cost_centers' => $paymentRequest->costCenters->pluck('name')->values(),
            'recipients' => $paymentRequest->recipients->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'is_recipient' => $paymentRequest->recipients->contains('id', $user->id),
            'is_owner' => $paymentRequest->user_id === $user->id,
            'created_at' => $paymentRequest->created_at->format('d/m/Y H:i'),
        ];

        return Inertia::render('PaymentRequests/Show', [
            'request' => $request,
        ]);
    }
}
