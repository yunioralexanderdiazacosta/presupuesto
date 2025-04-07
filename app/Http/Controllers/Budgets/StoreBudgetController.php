<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormBudgetRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;

class StoreBudgetController extends Controller
{
    public function __invoke(FormBudgetRequest $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        Budget::Create([
            'name' => $request->name,
            'observations' => $request->observations,
            'season_id' => $season_id,
            'team_id' => $user->team_id
        ]);
    }
}
