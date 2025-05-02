<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\LevelsExport;
use Maatwebsite\Excel\Facades\Excel;

class LevelsExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new LevelsExport($request->term), 'levels.xlsx');
    }
}
