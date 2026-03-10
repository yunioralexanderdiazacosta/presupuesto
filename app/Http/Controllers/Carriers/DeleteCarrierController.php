<?php

namespace App\Http\Controllers\Carriers;

use App\Models\Carrier;

class DeleteCarrierController
{
    public function __invoke(Carrier $carrier)
    {
        $carrier->delete();

        return back()->with('success', 'Transportista eliminado.');
    }
}
