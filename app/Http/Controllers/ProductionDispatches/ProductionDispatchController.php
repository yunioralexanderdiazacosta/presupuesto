<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Http\Controllers\Controller;
use App\Models\ProductionDispatch;
use App\Models\CostCenterVariety;
use App\Models\Exporter;
use App\Models\PackingHouse;
use App\Models\BinType;
use App\Models\BoxType;
use App\Models\Carrier;
use App\Models\FruitClassificationType;
use App\Models\Fruit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductionDispatchController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $seasonId = session('season_id');

        if (!$seasonId) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una temporada activa.');
        }

        $dispatches = ProductionDispatch::with([
                'costCenterVariety.costCenter',
                'costCenterVariety.variety',
                'costCenterVariety.fruit',
                'exporter',
                'packingHouse',
                'binType',
                'boxType',
                'carrier',
                'items',
            ])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->latest('dispatch_date')
            ->paginate(20);

        // Centros de costo con sus variedades (para el select anidado)
        $costCenterVarieties = CostCenterVariety::with(['costCenter', 'variety', 'fruit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $seasonId)
            ->get()
            ->map(fn($ccv) => [
                'value' => $ccv->id,
                'label' => ($ccv->costCenter->name ?? '') . ' - ' . ($ccv->variety->name ?? '') . ' (' . ($ccv->fruit->name ?? '') . ')',
                'fruit_id' => $ccv->fruit_id,
            ]);

        $exporters = Exporter::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'rut'])
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->name . ($e->rut ? ' (' . $e->rut . ')' : ''),
            ]);

        $packingHouses = PackingHouse::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($p) => [
                'value' => $p->id,
                'label' => $p->name,
            ]);

        $binTypes = BinType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => [
                'value' => $b->id,
                'label' => $b->name,
            ]);

        $boxTypes = BoxType::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => [
                'value' => $b->id,
                'label' => $b->name,
            ]);

        $carriers = Carrier::where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
            ]);

        // Clasificaciones agrupadas por fruit_id y tipo
        $classifications = FruitClassificationType::where('team_id', $user->team_id)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('fruit_id')
            ->map(fn($group) => $group->groupBy('type'));

        $fruits = Fruit::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('ProductionDispatches/Index', [
            'dispatches' => $dispatches,
            'costCenterVarieties' => $costCenterVarieties,
            'exporters' => $exporters,
            'packingHouses' => $packingHouses,
            'binTypes' => $binTypes,
            'boxTypes' => $boxTypes,
            'carriers' => $carriers,
            'classifications' => $classifications,
            'fruits' => $fruits,
        ]);
    }
}
