<?php

namespace App\Http\Controllers\DailyManagement;

use App\Exports\MonthlyYieldsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportMonthlyExcelController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'mode' => 'required|in:planilla,detalle,labor',
        ]);

        $filename = 'tarjas-' . $request->mode . '-' . $request->month . '.xlsx';

        return Excel::download(
            new MonthlyYieldsExport($request->month, $request->mode),
            $filename
        );
    }
}
