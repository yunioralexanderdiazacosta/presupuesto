<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Machinery;
use Barryvdh\DomPDF\Facade\Pdf;

class MachineriesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $machineries = Machinery::when($request->term, function ($query, $search) {
            $query->where('cod_machinery', 'like', '%'.$search.'%');
        })->with('typeMachinery')->where('team_id', $user->team_id)->get();

        $pdf = Pdf::loadView('pdfs.machineries', ['machineries' => $machineries]);

        return $pdf->stream();
    }
}
