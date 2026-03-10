<?php

namespace App\Http\Controllers\BinTypes;

use App\Models\BinType;
use Illuminate\Http\Request;

class UpdateBinTypeController
{
    public function __invoke(Request $request, BinType $binType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        $binType->update($validated);

        return back()->with('success', 'Tipo de bin actualizado.');
    }
}
