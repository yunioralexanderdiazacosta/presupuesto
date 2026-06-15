<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\CostCenter;
use App\Models\Season;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Month;

class IndexInvestmentController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $investments = Investment::with(['costCenters', 'season'])
            ->where('season_id', $season_id)
            ->latest()
            ->paginate(20);
       

        // Obtener los nombres de los meses
        $monthsArr = Month::orderBy('id')->pluck('name', 'id')->toArray();

        // Mapear los datos para el frontend
        // Usar through para transformar la colección paginada (Laravel lo soporta aunque marque error en el editor)
        $investments = $investments->through(function ($item) use ($monthsArr) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'month' => isset($monthsArr[$item->month_execute]) ? $monthsArr[$item->month_execute] : $item->month_execute,
                'month_execute' => $item->month_execute,
                'amount' => $item->amount ?? null,
                'cost_centers' => $item->costCenters->map(function($cc) {
                    return [
                        'id' => $cc->id,
                        'name' => $cc->name
                    ];
                }),
                'season' => $item->season ? [ 'id' => $item->season->id, 'name' => $item->season->name ] : null,
                'responsable' => $item->responsable,
                'estado' => $item->estado,
                'observations' => $item->observations,
            ];
        });
        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', function ($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name']);
        $seasons = Season::where('team_id', $user->team_id)->get(['id', 'name']);
        $users = User::all(['id', 'name']);
        $months = Month::orderBy('id')->pluck('name');
        return Inertia::render('Investments/Index', [
            'investments' => $investments,
            'costCenters' => $costCenters,
            'seasons' => $seasons,
            'users' => $users,
            'months' => $months,
        ]);
    }
}
