<?php

namespace App\Http\Controllers\Carriers;

use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreCarrierController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $validated['team_id'] = Auth::user()->team_id;

        Carrier::create($validated);

        return back()->with('success', 'Transportista creado.');
    }
}
