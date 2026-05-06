<?php

namespace App\Http\Controllers\FuelTanks;

use App\Models\FuelTank;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Level3;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FuelTanksController
{
    public function index()
    {
        $user = Auth::user();

        $tanks = FuelTank::with(['branch', 'product'])
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Productos de combustible del equipo
        $combustibleLevel3Ids = Level3::where('name', 'combustible')
            ->whereHas('level2.level1', function ($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->pluck('id');

        $fuelProducts = Product::whereIn('level3_id', $combustibleLevel3Ids)
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('FuelTanks/Index', [
            'tanks'        => $tanks,
            'branches'     => $branches,
            'fuelProducts' => $fuelProducts,
        ]);
    }
}
