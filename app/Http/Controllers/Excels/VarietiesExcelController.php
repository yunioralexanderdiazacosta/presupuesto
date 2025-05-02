<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\VarietiesExport;
use Maatwebsite\Excel\Facades\Excel;

class VarietiesExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(new VarietiesExport($request->term), 'varieties.xlsx');
    }
}
