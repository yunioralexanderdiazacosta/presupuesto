<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\InvoicesExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoicesExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new InvoicesExport($request->term), 'invoices.xlsx');
    }
}
