<?php

namespace App\Http\Controllers\BonusTypes;

use App\Http\Requests\BonusTypes\StoreBonusTypeRequest;
use App\Models\BonusType;
use Illuminate\Support\Facades\Auth;

class StoreBonusTypeController
{
    public function __invoke(StoreBonusTypeRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;
        $validated['default_amount'] = $validated['default_amount'] ?? 0;

        BonusType::create($validated);

        return redirect()->back()
            ->with('success', 'Tipo de bono registrado correctamente.');
    }
}
