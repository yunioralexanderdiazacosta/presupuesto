<?php

namespace App\Http\Controllers\Carriers;

use App\Models\Carrier;
use Illuminate\Http\Request;

class UpdateCarrierController
{
    public function __invoke(Request $request, Carrier $carrier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'is_active' => 'required|boolean',
        ]);

        $carrier->update($validated);

        return back()->with('success', 'Transportista actualizado.');
    }
}
