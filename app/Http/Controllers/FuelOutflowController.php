<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\FuelOutflow;
use App\Models\Machinery;
use App\Models\Operator;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\Counter;
// ...existing code...

class FuelOutflowController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }


    $fuelOutflows = FuelOutflow::with(['machinery.counter', 'operator', 'product', 'counter', 'costCenters.costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->paginate(20);
            
        // Transformar la colección dentro del paginador
        $fuelOutflows->getCollection()->transform(function ($item) {
            $item->costCenters = $item->costCenters->map(function($cc) {
                return [
                    'cost_center_id' => $cc->cost_center_id,
                    'name' => $cc->costCenter->name ?? '',
                    'observations' => $cc->observations ?? null,
                ];
            });
            return $item;
        });

        $machineries = Machinery::with('counter')->get(['id', 'cod_machinery', 'counter_id'])->map(function($machinery) {
            return [
                'value' => $machinery->id,
                'label' => $machinery->cod_machinery,
                'counter_id' => $machinery->counter_id,
                'counter_name' => $machinery->counter->name ?? null,
            ];
        });
        $operators = \App\Models\Operator::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name', 'team_id', 'season_id']);
        $costCenters = CostCenter::all(['id', 'name']);
        
        // Obtener productos de combustible (level3 = 'Combustible')
        $fuelProducts = Product::whereHas('level3', function($query) {
            $query->where('name', 'Combustible');
        })
        ->where('team_id', $user->team_id)
        ->get(['id', 'name'])
        ->map(function($product) {
            return [
                'value' => $product->id,
                'label' => $product->name
            ];
        });

        // Obtener todos los counters
        $counters = Counter::all(['id', 'name'])->map(function($counter) {
            return [
                'value' => $counter->id,
                'label' => $counter->name
            ];
        });

        return Inertia::render('FuelOutflows/Index', [
            'fuelOutflows' => $fuelOutflows,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
            'fuelProducts' => $fuelProducts,
            'counters' => $counters,
        ]);
    }
    // Aquí puedes agregar métodos agregados, reportes, exportaciones, etc.
}
