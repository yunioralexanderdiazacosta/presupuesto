<?php

namespace App\Http\Controllers\PackingHouses;

use App\Models\PackingHouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdatePackingHouseController
{
    public function __invoke(PackingHouse $packingHouse, Request $request)
    {
        abort_unless((int) $packingHouse->team_id === (int) Auth::user()->team_id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $packingHouse->update($validated);

        return back()->with('success', 'Packing actualizado correctamente.');
    }
}
