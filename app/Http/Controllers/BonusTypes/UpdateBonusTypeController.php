<?php

namespace App\Http\Controllers\BonusTypes;

use App\Http\Requests\BonusTypes\UpdateBonusTypeRequest;
use App\Models\BonusType;

class UpdateBonusTypeController
{
    public function __invoke(UpdateBonusTypeRequest $request, BonusType $bonusType)
    {
        $validated = $request->validated();
        $validated['default_amount'] = $validated['default_amount'] ?? 0;

        $bonusType->update($validated);

        return redirect()->route('bonus-types.index')
            ->with('success', 'Tipo de bono actualizado correctamente.');
    }
}
