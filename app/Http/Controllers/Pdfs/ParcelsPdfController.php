<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;
use Barryvdh\DomPDF\Facade\Pdf;

class ParcelsPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $parcels = Parcel::with(['companyReason:id,name', 'season:id,name'])->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->get();

         $pdf = Pdf::loadView('pdfs.parcels', ['parcels' => $parcels]);

        return $pdf->stream();   

    }
}
