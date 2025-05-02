<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CompanyReason;

class CompanyReasonsPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $companyReasons = CompanyReason::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('rut', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        $pdf = Pdf::loadView('pdfs.company-reasons', ['companyReasons' => $companyReasons]);

        return $pdf->stream(); 
    }
}
