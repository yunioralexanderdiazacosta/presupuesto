<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;


class SuppliersPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $suppliers = Supplier::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('rut', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();

        $pdf = Pdf::loadView('pdfs.suppliers', ['suppliers' => $suppliers]);

        return $pdf->stream();
    }
}
