<?php

namespace App\Http\Controllers\PackingHouses;

use App\Models\PackingHouse;

class DeletePackingHouseController
{
    public function __invoke(PackingHouse $packingHouse)
    {
        $packingHouse->delete();

        return back()->with('success', 'Packing eliminado correctamente.');
    }
}
