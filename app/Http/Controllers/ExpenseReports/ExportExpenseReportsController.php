<?php

namespace App\Http\Controllers\ExpenseReports;

use App\Http\Controllers\Controller;
use App\Exports\ExpenseReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportExpenseReportsController extends Controller
{
    public function __invoke()
    {
        return Excel::download(new ExpenseReportsExport(), 'rendiciones_gastos.xlsx');
    }
}
