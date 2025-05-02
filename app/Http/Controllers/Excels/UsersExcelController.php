<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UsersExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new UsersExport($request->term), 'users.xlsx');
    }
}
