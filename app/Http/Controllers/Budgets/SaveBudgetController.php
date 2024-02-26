<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SaveBudgetController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'budget_id' => 'required'
        ]);

        session(['budget_id' => $request->budget_id]);
    }
}
