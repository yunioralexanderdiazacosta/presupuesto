<?php

namespace App\Http\Controllers\BinTypes;

use App\Models\BinType;

class DeleteBinTypeController
{
    public function __invoke(BinType $binType)
    {
        $binType->delete();

        return back()->with('success', 'Tipo de bin eliminado.');
    }
}
