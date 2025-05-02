<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;
use App\Models\CompanyReason;
use App\Models\Season;
use Inertia\Inertia;

class ParcelsController extends Controller
{
    public function __invoke(Request $request)
    {   
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $companyReasons = CompanyReason::where('team_id', $user->team_id)->get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });

        $seasons = Season::where('team_id', $user->team_id)->get()->transform(function($season){
            return [
                'label' => $season->name,
                'value' => $season->id
            ];
        });

        $parcels = Parcel::with(['companyReason:id,name', 'season:id,name'])->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        return Inertia::render('Parcels', compact('companyReasons', 'seasons', 'parcels', 'term'));
    }
}
