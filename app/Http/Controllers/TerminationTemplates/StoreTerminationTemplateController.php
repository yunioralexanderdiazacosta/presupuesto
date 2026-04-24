<?php

namespace App\Http\Controllers\TerminationTemplates;

use App\Models\TerminationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreTerminationTemplateController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:docx|max:5120',
        ]);

        $user = Auth::user();

        $path = $request->file('file')->store('termination-templates/' . $user->team_id, 'local');

        TerminationTemplate::create([
            'team_id'   => $user->team_id,
            'name'      => $request->name,
            'file_path' => $path,
        ]);

        return redirect()->route('termination-templates.index')
            ->with('success', 'Plantilla guardada correctamente.');
    }
}
