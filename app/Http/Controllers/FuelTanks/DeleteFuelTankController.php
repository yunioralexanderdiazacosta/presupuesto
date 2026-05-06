<?php

namespace App\Http\Controllers\FuelTanks;

use App\Models\FuelTank;
use Illuminate\Support\Facades\Auth;

class DeleteFuelTankController
{
    public function __invoke(FuelTank $fuelTank)
    {
        abort_if($fuelTank->team_id !== Auth::user()->team_id, 403);

        $fuelTank->delete();

        return redirect()->route('fuel-tanks.index')->with('success', 'Estanque eliminado correctamente.');
    }
}
