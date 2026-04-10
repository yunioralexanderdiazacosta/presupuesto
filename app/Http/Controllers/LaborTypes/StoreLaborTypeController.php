<?php

namespace App\Http\Controllers\LaborTypes;

use App\Http\Requests\LaborTypes\StoreLaborTypeRequest;
use App\Models\LaborType;
use Illuminate\Support\Facades\Auth;

class StoreLaborTypeController
{
    public function __invoke(StoreLaborTypeRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;
        $validated['code'] = (LaborType::where('team_id', $user->team_id)->max('code') ?? 0) + 1;
        $validated['default_rate'] = $validated['default_rate'] ?? 0;
        $validated['default_bonus'] = $validated['default_bonus'] ?? 0;

        LaborType::create($validated);

        return redirect()->back()
            ->with('success', 'Labor registrada correctamente.');
    }
}
