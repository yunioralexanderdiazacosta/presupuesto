<?php

namespace App\Http\Controllers\Carriers;

use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateCarrierController
{
    public function __invoke(Request $request, Carrier $carrier)
    {
        abort_unless((int) $carrier->team_id === (int) Auth::user()->team_id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'is_active' => 'required|boolean',
        ]);

        $carrier->update($validated);

        return back()->with('success', 'Transportista actualizado.');
    }
}
