<?php

namespace App\Http\Controllers\FuelTanks;

use App\Models\FuelTank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreFuelTankController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'branch_id'  => 'nullable|exists:branches,id',
            'product_id' => 'nullable|exists:products,id',
            'capacity'   => 'nullable|numeric|min:0',
        ]);

        FuelTank::create([
            'team_id'    => Auth::user()->team_id,
            'branch_id'  => $request->branch_id,
            'product_id' => $request->product_id,
            'name'       => $request->name,
            'capacity'   => $request->capacity,
            'active'     => true,
        ]);

        return redirect()->route('fuel-tanks.index')->with('success', 'Estanque creado correctamente.');
    }
}
