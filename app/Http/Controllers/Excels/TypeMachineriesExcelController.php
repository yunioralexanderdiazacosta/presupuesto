<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\TypeMachineriesExport;
use Maatwebsite\Excel\Facades\Excel;

class TypeMachineriesExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new TypeMachineriesExport($request->term), 'type-machineries.xlsx');
    }
}
