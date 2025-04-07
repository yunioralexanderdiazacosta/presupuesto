<?php

namespace App\Http\Controllers\Machineries;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormMachineryRequest;
use App\Models\Machinery;

class StoreMachineryController extends Controller
{
    public function __invoke(FormMachineryRequest $request)
    {
        $user = Auth::user();

        Machinery::Create([
            'type_machinery_id' => $request->type_machinery_id,
            'company_reason_id' => $request->company_reason_id,
            'cod_machinery' => $request->cod_machinery,
            'brand' => $request->brand, 
            'volume' => $request->volume,
            'observations' => $request->observations, 
            'is_active' => $request->is_active,
            'team_id' => $user->team_id
        ]);
    }
}
