<?php

namespace App\Http\Controllers\BinTypes;

use App\Models\BinType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateBinTypeController
{
    public function __invoke(Request $request, BinType $binType)
    {
        abort_unless((int) $binType->team_id === (int) Auth::user()->team_id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        $binType->update($validated);

        return back()->with('success', 'Tipo de bin actualizado.');
    }
}
