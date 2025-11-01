<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasInventory;
use App\Models\Unit;
use App\Models\Level1;

class InventoryController extends Controller
{
    use HasInventory;

    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $team_id = $user->team_id;

        $inventory = $this->getInventory($team_id, $season_id);

        // Cargar unidades para el formulario de productos
        $units = Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
            ];
        });

        // Cargar niveles 1 para el formulario de productos
        $level1s = Level1::where('season_id', $season_id)->get()->transform(function($level){
            return [
                'label' => $level->name,
                'value' => $level->id
            ];
        });

        return Inertia::render('Inventory', [
            'inventory' => $inventory,
            'units' => $units,
            'level1s' => $level1s,
        ]);
    }
}
