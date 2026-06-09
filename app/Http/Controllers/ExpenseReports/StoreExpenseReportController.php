<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreExpenseReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $request->validate([
            'description' => 'nullable|string|max:500',
        ]);

        $number = ExpenseReport::nextNumber($user->team_id, $season_id);

        ExpenseReport::create([
            'team_id' => $user->team_id,
            'season_id' => $season_id,
            'user_id' => $user->id,
            'number' => $number,
            'description' => $request->description,
            'status' => 'borrador',
        ]);

        return redirect()->route('expense-reports.index')
            ->with('success', "Rendición {$number} creada exitosamente.");
    }
}
