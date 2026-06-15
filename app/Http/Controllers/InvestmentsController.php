<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\CostCenter;
use App\Models\Season;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvestmentsController extends Controller
{
    public function index()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $season_id = session('season_id');

        $investments = Investment::with(['costCenters', 'season'])->latest()->paginate(20);
        \Log::info('InvestmentsController@index', [
            'total' => $investments->total(),
            'ids' => collect($investments->items())->pluck('id')->toArray(),
        ]);
        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', function ($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name']);
        $seasons = Season::all(['id', 'name']);
        $users = User::all(['id', 'name']);
        return Inertia::render('Investments/Index', [
            'investments' => $investments,
            'costCenters' => $costCenters,
            'seasons' => $seasons,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'month_execute' => 'required|integer|min:1|max:12',
            'estado' => 'required|string',
            'responsable_id' => 'nullable|exists:users,id',
            'season_id' => 'nullable|exists:seasons,id',
            'observations' => 'nullable|string',
            'cost_centers' => 'required|array|min:1',
            'cost_centers.*' => 'exists:cost_centers,id',
        ]);
        $investment = Investment::create($data);
        $investment->costCenters()->sync($data['cost_centers']);
        return redirect()->back()->with('success', 'Inversión creada correctamente');
    }

    public function destroy(Investment $investment)
    {
        $investment->delete();
        return redirect()->back()->with('success', 'Inversión eliminada correctamente');
    }
}
