<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\CostCentersExport;
use Maatwebsite\Excel\Facades\Excel;

class CostCentersExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new CostCentersExport($request->term), 'cost-centers.xlsx');
    }
}
