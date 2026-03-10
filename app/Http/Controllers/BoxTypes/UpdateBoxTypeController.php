<?php

namespace App\Http\Controllers\BoxTypes;

use App\Models\BoxType;
use Illuminate\Http\Request;

class UpdateBoxTypeController
{
    public function __invoke(Request $request, BoxType $boxType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        $boxType->update($validated);

        return back()->with('success', 'Tipo de caja actualizado.');
    }
}
