<?php

namespace App\Http\Controllers\Budgets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Budget;
use App\Traits\CheckSeasonLocked;

class DeleteBudgetController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Budget $budget)
    {
        $this->abortIfSeasonLocked();
        $budget->delete();
    }
}
