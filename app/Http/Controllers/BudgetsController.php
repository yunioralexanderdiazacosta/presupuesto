<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Month;
use App\Models\Budget;
use App\Models\User;

class BudgetsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $months = Month::get()->transform(function($month){
            return [
                'label' => $month->name,
                'value' => $month->id
            ];
        });

        $budget_id = session('budget_id');

        $budgets = Budget::with('month')->where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('Budgets', compact('months', 'budgets', 'budget_id'));
    }
}
