<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PayrollDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetComparativePayrollMonthlyDetailController extends Controller
{
    use PayrollDataTrait;

    public function __invoke(Request $request)
    {
        $request->validate([
            'month_id'            => 'required|integer|between:1,12',
            'company_reason_ids'  => 'nullable|array',
            'company_reason_ids.*' => 'integer',
        ]);

        $user             = Auth::user();
        $team_id          = $user->team_id;
        $season_id        = session('season_id');
        $month_id         = (int) $request->month_id;
        $companyReasonIds = collect($request->input('company_reason_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (!$season_id) {
            return response()->json(['error' => 'Sin temporada activa'], 422);
        }

        $byLevel2 = $this->getPayrollByLevel2ForMonth(
            $team_id,
            $season_id,
            $month_id,
            count($companyReasonIds) > 0 ? $companyReasonIds : null
        );

        $rows = [];
        foreach ($byLevel2 as $level2Name => $data) {
            if (($data['total'] ?? 0) <= 0) {
                continue;
            }
            $rows[] = [
                'level1' => $data['level1'] ?? 'Sin clasificar',
                'level2' => $level2Name,
                'total_payroll' => (float) $data['total'],
            ];
        }

        return response()->json(['rows' => $rows]);
    }
}
