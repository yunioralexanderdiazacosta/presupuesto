<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parcel;
use App\Models\CostCenter;

class DeleteParcelController extends Controller
{
    public function __invoke(Parcel $parcel)
    {
        // Verificar si tiene centros de costo asociados
        $costCentersCount = CostCenter::where('parcel_id', $parcel->id)->count();

        if ($costCentersCount > 0) {
            return back()->with('error', "No se puede eliminar la parcela \"{$parcel->name}\" porque tiene {$costCentersCount} centro(s) de costo asociado(s).");
        }

        $parcel->delete();
        return back()->with('success', 'Parcela eliminada correctamente.');
    }
}
