<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\FruitsExport;
use Maatwebsite\Excel\Facades\Excel;

class FruitsExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new FruitsExport($request->term), 'fruits.xlsx');
    }
}
