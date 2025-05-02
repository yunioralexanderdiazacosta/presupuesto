<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\CostCenter;
use App\Models\Fruit;
use App\Models\Parcel;
use App\Models\DevelopmentState;
use Inertia\Inertia;

class CostCentersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $season_id = session('season_id');

        $season = Season::select('name')->where('id', $season_id)->first();

        $fruits = Fruit::where('team_id', $user->team_id)->get()->transform(function($fruit){
            return [
                'label' => $fruit->name,
                'value' => $fruit->id
            ];
        });

        $parcels = Parcel::where('team_id', $user->team_id)->get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });

        $costCenters = CostCenter::where('season_id', $season_id)->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->whereHas('season.team', function($query) use ($user){
            $query->where('team_id', $user->team_id);
        })->paginate(10)->withQueryString();

        $developmentStates = DevelopmentState::get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });

        return Inertia::render('CostCenters', compact('costCenters', 'season', 'fruits', 'parcels', 'developmentStates', 'term'));
    }   
}
