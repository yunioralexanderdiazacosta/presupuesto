<?php

namespace App\Http\Controllers\LaborTypes;

use App\Http\Requests\LaborTypes\UpdateLaborTypeRequest;
use App\Models\LaborType;

class UpdateLaborTypeController
{
    public function __invoke(UpdateLaborTypeRequest $request, LaborType $laborType)
    {
        $validated = $request->validated();
        $validated['default_rate'] = $validated['default_rate'] ?? 0;
        $validated['default_bonus'] = $validated['default_bonus'] ?? 0;

        $laborType->update($validated);

        return redirect()->route('labor-types.index')
            ->with('success', 'Labor actualizada correctamente.');
    }
}
