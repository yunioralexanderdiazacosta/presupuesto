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

        $budgets = Budget::where('team_id', $user->team_id)->get()->transform(function($budget){
            return [
                'label' => $budget->name,
                'value' => $budget->id
            ];
        });

        $costCenters = CostCenter::with('budget')->whereHas('budget.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->paginate(10);

        return Inertia::render('CostCenters', compact('costCenters', 'budgets'));
    }   
}
