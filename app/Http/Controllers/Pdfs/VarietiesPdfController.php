<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Variety;
use Barryvdh\DomPDF\Facade\Pdf;

class VarietiesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $varieties = Variety::with('fruit')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();

        $pdf = Pdf::loadView('pdfs.varieties', ['varieties' => $varieties]);

        return $pdf->stream();   
   
    }
}
