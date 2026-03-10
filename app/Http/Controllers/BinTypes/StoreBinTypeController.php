<?php

namespace App\Http\Controllers\BinTypes;

use App\Models\BinType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreBinTypeController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $validated['team_id'] = Auth::user()->team_id;

        BinType::create($validated);

        return back()->with('success', 'Tipo de bin creado.');
    }
}
