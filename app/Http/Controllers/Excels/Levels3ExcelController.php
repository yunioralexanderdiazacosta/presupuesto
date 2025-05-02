<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\Levels3Export;
use App\Models\Level2;
use Maatwebsite\Excel\Facades\Excel;

class Levels3ExcelController extends Controller
{
     public function __invoke(Level2 $level2, Request $request)
    {
        return Excel::download(new Levels3Export($request->term, $level2->id), 'levels3.xlsx');
    }
}
