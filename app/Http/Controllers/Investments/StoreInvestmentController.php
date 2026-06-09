<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreInvestmentController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'month_execute' => 'required|integer|min:1|max:12',
            'amount' => 'nullable|numeric',
            'estado' => 'required|string',
            'responsable' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'cost_centers' => 'required|array|min:1',
            'cost_centers.*' => 'exists:cost_centers,id',
        ]);
        // Obtener temporada desde sesión
        $data['season_id'] = session('season_id');
        $investment = Investment::create($data);
        $investment->costCenters()->sync($data['cost_centers']);
        return redirect()->back()->with('success', 'Inversión creada correctamente');
    }
}
