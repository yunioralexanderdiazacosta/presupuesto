<?php

namespace App\Http\Controllers\ContractTemplates;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreContractTemplateController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:docx|max:5120',
        ]);

        $user = Auth::user();

        $path = $request->file('file')->store('contract-templates/' . $user->team_id, 'local');

        ContractTemplate::create([
            'team_id' => $user->team_id,
            'name' => $request->name,
            'file_path' => $path,
        ]);

        return redirect()->route('contract-templates.index')
            ->with('success', 'Plantilla guardada correctamente.');
    }
}
