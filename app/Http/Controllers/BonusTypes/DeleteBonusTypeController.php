<?php

namespace App\Http\Controllers\BonusTypes;

use App\Models\BonusType;

class DeleteBonusTypeController
{
    public function __invoke(BonusType $bonusType)
    {
        $bonusType->delete();

        return redirect()->back()
            ->with('success', 'Tipo de bono eliminado correctamente.');
    }
}
