<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Level1;
use Barryvdh\DomPDF\Facade\Pdf;

class LevelsPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $levels = Level1::where('team_id', $user->team_id)->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('season_id', $season_id)->get();   

        $pdf = Pdf::loadView('pdfs.levels', ['levels' => $levels]);

        return $pdf->stream();   
    }
}
