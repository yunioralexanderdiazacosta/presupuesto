<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level3;
use App\Models\Level2;
use Barryvdh\DomPDF\Facade\Pdf;

class Levels3PdfController extends Controller
{
    public function __invoke(Request $request, Level2 $level2)
    {
        $levels = Level3::with('level2', 'level2.level1')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level2_id', $level2->id)->get();

        $pdf = Pdf::loadView('pdfs.levels3', ['levels' => $levels]);

        return $pdf->stream();   

    }
}
