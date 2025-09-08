<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreInvestmentController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'month_execute' => 'required|integer|min:1|max:12',
            'estado' => 'required|string',
            'responsable_id' => 'nullable|exists:users,id',
            'season_id' => 'nullable|exists:seasons,id',
            'observations' => 'nullable|string',
            'cost_centers' => 'required|array|min:1',
            'cost_centers.*' => 'exists:cost_centers,id',
        ]);
        $investment = Investment::create($data);
        $investment->costCenters()->sync($data['cost_centers']);
        return redirect()->back()->with('success', 'Inversión creada correctamente');
    }
}
