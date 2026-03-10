<?php

namespace App\Http\Controllers\BoxTypes;

use App\Models\BoxType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreBoxTypeController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $validated['team_id'] = Auth::user()->team_id;

        BoxType::create($validated);

        return back()->with('success', 'Tipo de caja creado.');
    }
}
