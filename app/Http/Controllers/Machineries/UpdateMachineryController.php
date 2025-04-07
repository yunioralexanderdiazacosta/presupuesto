<?php

namespace App\Http\Controllers\Machineries;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormMachineryRequest;
use App\Models\Machinery;

class UpdateMachineryController extends Controller
{
    public function __invoke(Machinery $machinery, FormMachineryRequest $request)
    {
        $machinery->cod_machinery = $request->cod_machinery;
        $machinery->type_machinery_id = $request->type_machinery_id;
        $machinery->company_reason_id = $request->company_reason_id;
        $machinery->volume = $request->volume;
        $machinery->brand = $request->brand;
        $machinery->observations = $request->observations;
        $machinery->is_active = $request->is_active;
        $machinery->save();
    }
}
