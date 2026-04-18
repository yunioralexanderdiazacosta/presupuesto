<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Season;
use App\Models\Level1;
use App\Models\Budget;
use App\Models\CostCenter;
use App\Models\Invoice;
use App\Models\Parcel;
use App\Models\Field;

class DeleteSeasonController extends Controller
{
    public function __invoke(Season $season)
    {
        // Verificar si hay datos asociados que se perderían
        $dependencies = [];

        $count = Level1::where('season_id', $season->id)->count();
        if ($count) $dependencies[] = "$count nivel(es) de presupuesto";

        $count = Budget::where('season_id', $season->id)->count();
        if ($count) $dependencies[] = "$count presupuesto(s)";

        $count = CostCenter::where('season_id', $season->id)->count();
        if ($count) $dependencies[] = "$count centro(s) de costo";

        $count = Invoice::where('season_id', $season->id)->count();
        if ($count) $dependencies[] = "$count factura(s)";

        $count = Parcel::where('season_id', $season->id)->count();
        if ($count) $dependencies[] = "$count parcela(s)";

        $count = Field::where('season_id', $season->id)->count();
        if ($count) $dependencies[] = "$count campo(s)";

        if (!empty($dependencies)) {
            return back()->with('error',
                'No se puede eliminar la temporada "' . $season->name . '" porque tiene datos asociados: ' . implode(', ', $dependencies) . '. Elimine primero esos registros.'
            );
        }

        $season->delete();

        return back()->with('success', 'Temporada eliminada correctamente.');
    }
}
