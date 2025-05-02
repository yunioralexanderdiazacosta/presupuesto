<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\CostCenter;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class CostCentersPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');
        
        $costCenters = CostCenter::where('season_id', $season_id)->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->get();

        $pdf = Pdf::loadView('pdfs.cost-centers', ['costCenters' => $costCenters]);

        return $pdf->stream();   
    }
}
