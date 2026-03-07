<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostCenterVariety;
use Illuminate\Support\Facades\Auth;

class GetCostCenterVarietiesController extends Controller
{
    public function __invoke($costCenterId)
    {
        $season_id = session('season_id');

        return CostCenterVariety::with(['variety', 'fruit', 'rootstock', 'developmentState'])
            ->where('cost_center_id', $costCenterId)
            ->where('season_id', $season_id)
            ->orderBy('id')
            ->get()
            ->map(fn($v) => [
                'id'                   => $v->id,
                'cost_center_id'       => $v->cost_center_id,
                'fruit_id'             => $v->fruit_id,
                'variety_id'           => $v->variety_id,
                'rootstock_id'         => $v->rootstock_id,
                'development_state_id' => $v->development_state_id,
                'surface'              => (float) $v->surface,
                'year_plantation'      => $v->year_plantation,
                'observations'         => $v->observations,
                'fruit_name'           => $v->fruit?->name,
                'variety_name'         => $v->variety?->name,
                'rootstock_name'       => $v->rootstock?->name,
                'development_state_name' => $v->developmentState?->name,
            ]);
    }
}
