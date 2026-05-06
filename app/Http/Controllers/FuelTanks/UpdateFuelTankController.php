<?php

namespace App\Http\Controllers\FuelTanks;

use App\Models\FuelTank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateFuelTankController
{
    public function __invoke(Request $request, FuelTank $fuelTank)
    {
        // Sólo el team dueño puede editar
        abort_if($fuelTank->team_id !== Auth::user()->team_id, 403);

        $request->validate([
            'name'       => 'required|string|max:100',
            'branch_id'  => 'nullable|exists:branches,id',
            'product_id' => 'nullable|exists:products,id',
            'capacity'   => 'nullable|numeric|min:0',
            'active'     => 'boolean',
        ]);

        $fuelTank->update($request->only('name', 'branch_id', 'product_id', 'capacity', 'active'));

        return redirect()->route('fuel-tanks.index')->with('success', 'Estanque actualizado correctamente.');
    }
}
