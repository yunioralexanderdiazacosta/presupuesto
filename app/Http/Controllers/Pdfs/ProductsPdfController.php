<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductsPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $products = Product::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->with('unit')->where('team_id', $user->team_id)->get();

        $pdf = Pdf::loadView('pdfs.products', ['products' => $products]);

        return $pdf->stream();
    }
}
