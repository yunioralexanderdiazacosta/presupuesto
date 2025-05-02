<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level1;
use App\Models\Level2;
use Barryvdh\DomPDF\Facade\Pdf;

class Levels2PdfController extends Controller
{
    public function __invoke(Level1 $level1, Request $request)
    {
        $term = $request->term ?? '';  

        $levels = Level2::with('level1')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('level1_id', $level1->id)->get();

        $pdf = Pdf::loadView('pdfs.levels2', ['levels' => $levels]);

        return $pdf->stream();   
    }
}
