<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutflowRequest;
use App\Models\Outflow;
use Illuminate\Support\Facades\Auth;

class StoreOutflowController extends Controller
{
    public function __invoke(OutflowRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $outflow = Outflow::create($data);
        if (isset($data['cost_centers']) && is_array($data['cost_centers'])) {
            foreach ($data['cost_centers'] as $costCenterId) {
                $outflow->costCenters()->create([
                    'cost_center_id' => $costCenterId,
                    'observations' => $data['observations'] ?? null,
                ]);
            }
        }
        return redirect()->route('outflows.index')->with('success', 'Salida registrada correctamente.');
    }
}
