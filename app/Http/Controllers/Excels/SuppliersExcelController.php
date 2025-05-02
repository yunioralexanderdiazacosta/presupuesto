<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\SuppliersExport;
use Maatwebsite\Excel\Facades\Excel;

class SuppliersExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new SuppliersExport($request->term), 'suppliers.xlsx');
    }
}
