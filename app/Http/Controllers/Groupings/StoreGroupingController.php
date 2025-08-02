<?php

namespace App\Http\Controllers\Groupings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Http\Requests\Groupings\StoreGroupingRequest;
use App\Models\Grouping;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;

class StoreGroupingController extends Controller
{
    /**
     * Handle the incoming grouping create request.
     */
    public function __invoke(StoreGroupingRequest $request)
    {
        // Crear grouping con datos validados
        $grouping = Grouping::create($request->validated());

        // Asignar grouping_id a cost centers seleccionados
        CostCenter::whereIn('id', $request->cost_center_ids)
            ->update(['grouping_id' => $grouping->id]);

        // Retornar a listado de groupings
        return redirect()->route('groupings.index');
    }
}
