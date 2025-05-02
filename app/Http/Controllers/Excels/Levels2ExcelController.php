<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\Levels2Export;
use App\Models\Level1;
use Maatwebsite\Excel\Facades\Excel;

class Levels2ExcelController extends Controller
{
     public function __invoke(Level1 $level1, Request $request)
    {
        return Excel::download(new Levels2Export($request->term, $level1->id), 'levels2.xlsx');
    }
}
