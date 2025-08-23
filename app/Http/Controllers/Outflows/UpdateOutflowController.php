<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutflowRequest;
use App\Models\Outflow;

class UpdateOutflowController extends Controller
{
    public function __invoke(OutflowRequest $request, Outflow $outflow)
    {
        $data = $request->validated();
        $outflow->update($data);
        // Actualizar cost centers si es necesario
        // ...
        return redirect()->route('outflows.index')->with('success', 'Salida actualizada correctamente.');
    }
}
