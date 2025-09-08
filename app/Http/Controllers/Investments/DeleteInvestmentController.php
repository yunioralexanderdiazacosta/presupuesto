<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\Investment;

class DeleteInvestmentController extends Controller
{
    public function __invoke(Investment $investment)
    {
        $investment->costCenters()->detach();
        $investment->delete();
        return redirect()->back()->with('success', 'Inversión eliminada correctamente');
    }
}
