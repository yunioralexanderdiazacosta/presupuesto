<?php

namespace App\Http\Controllers\Exporters;

use App\Models\Exporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreExporterController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rut' => 'nullable|string|max:20',
            'contact' => 'nullable|string|max:255',
        ]);

        $validated['team_id'] = Auth::user()->team_id;

        Exporter::create($validated);

        return back()->with('success', 'Exportadora registrada correctamente.');
    }
}
