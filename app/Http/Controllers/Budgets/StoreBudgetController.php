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

        Budget::Create([
            'name' => $request->name,
            'observations' => $request->observations,
            'season' => $request->season,
            'month_id' => $request->month_id,
            'team_id' => $user->team_id
        ]);
    }
}
