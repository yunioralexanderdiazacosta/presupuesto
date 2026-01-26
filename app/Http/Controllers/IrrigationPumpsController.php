<?php

namespace App\Http\Controllers;

use App\Models\IrrigationPump;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IrrigationPumpsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        
        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        $term = $request->get('term', '');

        $irrigationPumps = IrrigationPump::with('sectors')
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($term, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                      ->orWhere('code', 'like', '%'.$search.'%')
                      ->orWhere('brand', 'like', '%'.$search.'%')
                      ->orWhere('model', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(15);
        
        // Agregar información de sectores en uso
        foreach ($irrigationPumps as $pump) {
            foreach ($pump->sectors as $sector) {
                $sector->orders_count = DB::table('fertilizer_order_irrigation_sector')
                    ->where('irrigation_sector_id', $sector->id)
                    ->count();
            }
        }

        return Inertia::render('IrrigationPumps/Index', [
            'irrigationPumps' => $irrigationPumps,
            'term' => $term,
        ]);
    }
}
