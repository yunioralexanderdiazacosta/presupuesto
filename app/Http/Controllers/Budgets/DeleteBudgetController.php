<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Budget;

class DeleteBudgetController extends Controller
{
    public function __invoke(Budget $budget)
    {
        $budget->delete();
    }
}
