<?php

namespace App\Http\Controllers\BonusTypes;

use App\Models\BonusType;

class DeleteBonusTypeController
{
    public function __invoke(BonusType $bonusType)
    {
        $bonusType->delete();

        return redirect()->route('bonus-types.index')
            ->with('success', 'Tipo de bono eliminado correctamente.');
    }
}
