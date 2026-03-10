<?php

namespace App\Http\Controllers\PackingHouses;

use App\Models\PackingHouse;
use Illuminate\Http\Request;

class UpdatePackingHouseController
{
    public function __invoke(PackingHouse $packingHouse, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $packingHouse->update($validated);

        return back()->with('success', 'Packing actualizado correctamente.');
    }
}
