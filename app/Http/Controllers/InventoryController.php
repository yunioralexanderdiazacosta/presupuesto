<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasInventory;

class InventoryController extends Controller
{
    use HasInventory;

    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $team_id = $user->team_id;

        $inventory = $this->getInventory($team_id, $season_id);

        return Inertia::render('Inventory', [
            'inventory' => $inventory,
        ]);
    }
}
