<?php

namespace App\Http\Controllers\PaymentRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequests\StorePaymentRequestRequest;
use App\Mail\PaymentRequestCreated;
use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class StorePaymentRequestController extends Controller
{
    public function __invoke(StorePaymentRequestRequest $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');
        $validated = $request->validated();

        $storedFiles = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $uploadedFile) {
                $storedFiles[] = [
                    'path' => $uploadedFile->store("payment-requests/{$teamId}/" . date('Y'), 'public'),
                    'original_name' => $uploadedFile->getClientOriginalName(),
                ];
            }
        }

        DB::beginTransaction();
        try {
            $paymentRequest = PaymentRequest::create([
                'team_id' => $teamId,
                'season_id' => $seasonId,
                'user_id' => $user->id,
                'number' => PaymentRequest::nextNumber($teamId, $seasonId),
                'date' => $validated['date'],
                'character' => $validated['character'],
                'concept_observations' => $validated['concept_observations'] ?? null,
                'status' => 'pendiente',
            ]);

            foreach ($storedFiles as $file) {
                $paymentRequest->files()->create([
                    'file_path' => $file['path'],
                    'original_name' => $file['original_name'],
                ]);
            }

            $paymentRequest->costCenters()->sync($validated['cost_center_ids']);
            $paymentRequest->recipients()->sync($validated['user_ids']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($storedFiles as $file) {
                Storage::disk('public')->delete($file['path']);
            }
            return back()->withErrors(['error' => 'Error al crear la solicitud: ' . $e->getMessage()]);
        }

        $paymentRequest->load(['user', 'team', 'costCenters', 'recipients', 'files']);

        foreach ($paymentRequest->recipients as $recipient) {
            if ($recipient->email) {
                Mail::to($recipient->email)->send(new PaymentRequestCreated($paymentRequest, $recipient));
            }
        }

        return redirect()->route('payment-requests.index')
            ->with('success', "Solicitud {$paymentRequest->number} enviada.");
    }
}
