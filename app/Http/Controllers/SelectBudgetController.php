<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;
use Inertia\Inertia;

class SelectBudgetController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $budgets = Budget::select('id', 'name')->where('team_id', $user->team_id)->get()->transform(function($budget){
            return [
                'label' => $budget->name,
                'value' => $budget->id
            ];
        });

        return Inertia::render('SelectBudget', compact('budgets'));
    }
}
