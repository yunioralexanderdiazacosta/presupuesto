<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\SeasonsExport;
use Maatwebsite\Excel\Facades\Excel;

class SeasonsExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new SeasonsExport($request->term), 'seasons.xlsx');
    }
}
