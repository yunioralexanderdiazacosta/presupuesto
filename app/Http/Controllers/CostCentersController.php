<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;
use App\Models\CostCenter;
use Inertia\Inertia;

class CostCentersController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $budget_id = session('budget_id');

        $budgets = [];

        $budget = Budget::select('name')->where('id', $budget_id)->first();

        $costCenters = CostCenter::where('budget_id', $budget_id)->whereHas('budget.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->paginate(10);

        return Inertia::render('CostCenters', compact('costCenters', 'budget', 'budgets'));
    }   
}
