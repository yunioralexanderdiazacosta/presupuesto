<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormBudgetRequest;
use App\Models\Budget;
use App\Traits\CheckSeasonLocked;

class UpdateBudgetController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Budget $budget, FormBudgetRequest $request)
    {
        $this->abortIfSeasonLocked();
        $budget->name = $request->name;
        $budget->observations = $request->observations;
        $budget->save();
    }
}
