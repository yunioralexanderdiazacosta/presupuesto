<?php

namespace App\Http\Controllers\BoxTypes;

use App\Models\BoxType;

class DeleteBoxTypeController
{
    public function __invoke(BoxType $boxType)
    {
        $boxType->delete();

        return back()->with('success', 'Tipo de caja eliminado.');
    }
}
