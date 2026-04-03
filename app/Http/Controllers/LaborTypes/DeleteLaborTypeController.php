<?php

namespace App\Http\Controllers\LaborTypes;

use App\Models\LaborType;

class DeleteLaborTypeController
{
    public function __invoke(LaborType $laborType)
    {
        $laborType->delete();

        return redirect()->route('labor-types.index')
            ->with('success', 'Labor eliminada correctamente.');
    }
}
