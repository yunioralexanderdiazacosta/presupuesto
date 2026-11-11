<?php

namespace App\Http\Controllers\BoxTypes;

use App\Models\BoxType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateBoxTypeController
{
    public function __invoke(Request $request, BoxType $boxType)
    {
        abort_unless((int) $boxType->team_id === (int) Auth::user()->team_id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        $boxType->update($validated);

        return back()->with('success', 'Tipo de caja actualizado.');
    }
}
