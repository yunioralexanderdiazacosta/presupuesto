<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\BudgetsExport;
use Maatwebsite\Excel\Facades\Excel;

class BudgetsExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(new BudgetsExport($request->term), 'budgets.xlsx');
    }
}
