<?php

namespace App\Http\Controllers\Exporters;

use App\Models\Exporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateExporterController
{
    public function __invoke(Exporter $exporter, Request $request)
    {
        abort_unless((int) $exporter->team_id === (int) Auth::user()->team_id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rut' => 'nullable|string|max:20',
            'contact' => 'nullable|string|max:255',
        ]);

        $exporter->update($validated);

        return back()->with('success', 'Exportadora actualizada correctamente.');
    }
}
