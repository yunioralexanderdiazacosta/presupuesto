<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\Levels4Export;
use App\Models\Level3;
use Maatwebsite\Excel\Facades\Excel;

class Levels4ExcelController extends Controller
{
     public function __invoke(Request $request, Level3 $level3)
    {
        return Excel::download(new Levels4Export($request->term, $level3->id), 'levels4.xlsx');
    }
}
