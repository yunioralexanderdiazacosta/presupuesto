<?php

namespace App\Http\Controllers\DailyManagement;

use App\Exports\YieldTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Parcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class YieldTemplateExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date'      => 'required|date',
            'parcel_id' => 'nullable|integer|exists:parcels,id',
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

        $contracts = $query->get()
            ->sortBy(fn($c) => $c->employee->paternal_surname . ' ' . $c->employee->first_name);

        $parcel = $request->parcel_id ? Parcel::find($request->parcel_id) : null;

        $filename = 'plantilla-tarjas-' . $date->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new YieldTemplateExport(
                $contracts,
                $date,
                $parcel ? $parcel->name : 'Todas',
                $user->currentTeam->name ?? ''
            ),
            $filename
        );
    }
}
