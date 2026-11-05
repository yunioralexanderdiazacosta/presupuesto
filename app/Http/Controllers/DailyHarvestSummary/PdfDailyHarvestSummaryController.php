<?php

namespace App\Http\Controllers\DailyHarvestSummary;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyHarvestSummary\DailyHarvestSummaryRequest;
use App\Models\Branch;
use App\Models\CompanyReason;
use App\Services\DailyHarvestSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PdfDailyHarvestSummaryController extends Controller
{
    public function __invoke(DailyHarvestSummaryRequest $request, DailyHarvestSummaryService $service)
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        if (!$seasonId) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una temporada activa.');
        }

        $summary = $service->build(
            $user->team_id,
            $seasonId,
            $request->summaryDate(),
            $request->branchIds(),
            $request->reasonIds(),
        );

        $branchNames = Branch::where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->whereIn('id', $request->branchIds())
            ->orderBy('name')
            ->pluck('name');

        $reasonNames = CompanyReason::where('team_id', $user->team_id)
            ->whereIn('id', $request->reasonIds())
            ->orderBy('name')
            ->pluck('name');

        $pdf = Pdf::loadView('pdfs.daily-harvest-summary', [
            'summary' => $summary,
            'dateLabel' => Carbon::parse($summary['date'])->format('d-m-Y'),
            'branchNames' => $branchNames,
            'reasonNames' => $reasonNames,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('resumen-diario-cosecha-' . $summary['date'] . '.pdf');
    }
}
