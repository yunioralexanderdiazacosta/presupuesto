<?php

namespace App\Http\Controllers\Excels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductsExcelController extends Controller
{
     public function __invoke(Request $request)
    {
        return Excel::download(new ProductsExport($request->term), 'products.xlsx');
    }
}
