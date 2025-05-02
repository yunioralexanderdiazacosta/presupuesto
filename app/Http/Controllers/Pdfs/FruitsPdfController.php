<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Fruit;
use Barryvdh\DomPDF\Facade\Pdf;


class FruitsPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $fruits = Fruit::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();

        $pdf = Pdf::loadView('pdfs.fruits', ['fruits' => $fruits]);

        return $pdf->stream();   
    }
}
