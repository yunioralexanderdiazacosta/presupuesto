<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\MachineriesExport;
use Maatwebsite\Excel\Facades\Excel;

class MachineriesExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new MachineriesExport($request->term), 'machineries.xlsx');
    }
}
