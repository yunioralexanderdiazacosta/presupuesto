<?php

namespace App\Http\Controllers\DailyHarvestSummary;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyHarvestSummary\DailyHarvestSummaryRequest;
use App\Models\Branch;
use App\Models\CompanyReason;
use App\Services\DailyHarvestSummaryService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyHarvestSummaryController extends Controller
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

        $branches = Branch::where('season_id', $seasonId)
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $companyReasons = CompanyReason::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('DailyHarvestSummary/Index', [
            'summary' => $summary,
            'filters' => [
                'date' => $summary['date'],
                'branch_ids' => $request->branchIds(),
                'reason_ids' => $request->reasonIds(),
            ],
            'branches' => $branches,
            'companyReasons' => $companyReasons,
        ]);
    }
}
