<?php

namespace App\Http\Controllers\PackingHouses;

use App\Models\PackingHouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StorePackingHouseController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $validated['team_id'] = Auth::user()->team_id;

        PackingHouse::create($validated);

        return back()->with('success', 'Packing registrado correctamente.');
    }
}
