<?php

namespace App\Http\Controllers\Estimates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\UpdateEstimateRequest;
use App\Models\Estimate;

class UpdateEstimateController extends Controller
{
    public function __invoke(Estimate $estimate, UpdateEstimateRequest $request)
    {
        $estimate->product_name = $request->product_name;
        $estimate       ->price        = $request->price;
        $estimate->quantity     = $request->quantity;
        $estimate->observations = $request->observations;
        $estimate->subfamily_id = $request->subfamily_id;
        $estimate->unit_id      = $request->unit_id;
        $estimate->team_id = auth()->user()->team_id;
        $estimate->season_id = session('season_id');
        $estimate->save();

        $estimate->items()->delete();
        
        
    }
}
