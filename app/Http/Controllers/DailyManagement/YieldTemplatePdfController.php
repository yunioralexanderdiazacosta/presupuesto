<?php

namespace App\Http\Controllers\DailyManagement;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\Parcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class YieldTemplatePdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'parcel_id' => 'nullable|integer|exists:parcels,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $user = Auth::user();
        $date = Carbon::parse($request->date);

        $query = Contract::with('employee')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->whereHas('employee', fn($q) => $q->where('is_active', true));

        if ($request->parcel_id) {
            $query->where('parcel_id', $request->parcel_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $contracts = $query->get()
            ->sortBy(fn($c) => $c->employee->paternal_surname . ' ' . $c->employee->first_name);

        $parcel = $request->parcel_id ? Parcel::find($request->parcel_id) : null;
        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $pdf = Pdf::loadView('pdfs.yield-template', [
            'contracts' => $contracts,
            'date' => $date,
            'parcelName' => $parcel ? $parcel->name : 'Todas',
            'branchName' => $branch ? $branch->name : null,
            'teamName' => $user->currentTeam->name ?? '',
        ]);

        $pdf->setPaper('letter', 'landscape');

        $filename = 'plantilla-tarjas-' . $date->format('Y-m-d') . '.pdf';

        return $pdf->stream($filename);
    }
}
