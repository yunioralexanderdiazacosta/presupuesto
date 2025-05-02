<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\TypeMachinery;
use Barryvdh\DomPDF\Facade\Pdf;

class TypeMachineriesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $type_machineries = TypeMachinery::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();


        $pdf = Pdf::loadView('pdfs.type-machineries', ['typeMachineries' => $type_machineries]);

        return $pdf->stream();
    }
}
