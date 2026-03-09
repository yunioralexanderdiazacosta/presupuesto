<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;
//use App\Models\CompanyReason;
use App\Models\Season;
use Inertia\Inertia;

class ParcelsController extends Controller
{
    public function __invoke(Request $request)
    {   
        $user = Auth::user();

        $term = $request->term ?? ''; 

      /*  $companyReasons = CompanyReason::where('team_id', $user->team_id)->get()->transform(function($company){
            return [
                'label' => $company->name,
                'value' => $company->id
            ];
        });
*/
        $seasons = Season::where('team_id', $user->team_id)->get()->transform(function($season){
            return [
                'label' => $season->name,
                'value' => $season->id
            ];
        });

        $season_id = session('season_id');

        $parcels = Parcel::with([ 'season:id,name'])->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)
          ->when($season_id, fn($q) => $q->where('season_id', $season_id), fn($q) => $q->whereNull('season_id'))
          ->paginate(10)->withQueryString();

        // Verificar parcelas pendientes de traspaso desde la temporada anterior
        $pendingTransferCount = 0;
        $previousSeasonName = null;

        if ($season_id) {
            $previousSeason = Season::where('team_id', $user->team_id)
                ->where('id', '<', $season_id)
                ->latest('id')
                ->first();

            if ($previousSeason) {
                $previousSeasonName = $previousSeason->name;
                $existingNames = Parcel::where('team_id', $user->team_id)
                    ->where('season_id', $season_id)
                    ->pluck('name')->toArray();
                $pendingTransferCount = Parcel::where('team_id', $user->team_id)
                    ->where('season_id', $previousSeason->id)
                    ->whereNotIn('name', $existingNames)
                    ->count();
            }
        }

        return Inertia::render('Parcels', compact('seasons', 'parcels', 'term', 'pendingTransferCount', 'previousSeasonName'));
    }

    public function previousSeason()
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return response()->json(['name' => null, 'count' => 0]);
        }

        $previousSeason = Season::where('team_id', $user->team_id)
            ->where('id', '<', $season_id)
            ->latest('id')
            ->first();

        if (!$previousSeason) {
            return response()->json(['name' => null, 'count' => 0, 'pending' => 0]);
        }

        $existingNames = Parcel::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->pluck('name')->toArray();

        $pending = Parcel::where('team_id', $user->team_id)
            ->where('season_id', $previousSeason->id)
            ->whereNotIn('name', $existingNames)
            ->count();

        return response()->json(['name' => $previousSeason->name, 'count' => $pending, 'pending' => $pending]);
    }
}
