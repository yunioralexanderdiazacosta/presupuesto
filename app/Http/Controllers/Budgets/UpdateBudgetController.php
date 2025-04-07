<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormBudgetRequest;
use App\Models\Budget;

class UpdateBudgetController extends Controller
{
    public function __invoke(Budget $budget, FormBudgetRequest $request)
    {
        $budget->name = $request->name;
        $budget->observations = $request->observations;
        $budget->save();
    }
}
