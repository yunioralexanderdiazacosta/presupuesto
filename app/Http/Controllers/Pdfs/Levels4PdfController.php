<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level4;
use App\Models\Level3;
use Barryvdh\DomPDF\Facade\Pdf;

class Levels4PdfController extends Controller
{
    public function __invoke(Request $request, Level3 $level3)
    {
        $levels = Level4::with('level3', 'level3.level2', 'level3.level2.level1')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level3_id', $level3->id)->get(); 

        $pdf = Pdf::loadView('pdfs.levels4', ['levels' => $levels]);

        return $pdf->stream();  
    }
}
