<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PhenologicalStage;
use App\Models\Fruit;
use Inertia\Inertia;

class PhenologicalStagesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $phenologicalStages = PhenologicalStage::with('fruit')
            ->when($request->term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('fruit', function($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    });
            })
            ->where('team_id', $user->team_id)
            ->paginate(10);

        // Obtener frutas del equipo para el formulario
        $fruits = Fruit::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function($fruit) {
                return [
                    'value' => $fruit->id,
                    'label' => $fruit->name,
                ];
            });

        return Inertia::render('PhenologicalStages', compact('phenologicalStages', 'fruits', 'term'));
    }
}
