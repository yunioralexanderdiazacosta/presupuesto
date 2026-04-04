<?php

namespace App\Http\Controllers\LaborTypes;

use App\Models\LaborType;

class DeleteLaborTypeController
{
    public function __invoke(LaborType $laborType)
    {
        $laborType->delete();

        return redirect()->back()
            ->with('success', 'Labor eliminada correctamente.');
    }
}
