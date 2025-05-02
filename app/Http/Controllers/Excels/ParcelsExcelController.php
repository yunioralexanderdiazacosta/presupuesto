<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ParcelsExport;
use Maatwebsite\Excel\Facades\Excel;

class ParcelsExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new ParcelsExport($request->term), 'parcels.xlsx');
    }
}
