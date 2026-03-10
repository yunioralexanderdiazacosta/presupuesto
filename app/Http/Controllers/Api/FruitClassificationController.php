<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FruitClassificationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FruitClassificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $classifications = FruitClassificationType::where('team_id', $user->team_id)
            ->when($request->fruit_id, fn($q) => $q->where('fruit_id', $request->fruit_id))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return response()->json($classifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fruit_id' => 'required|exists:fruits,id',
            'type' => 'required|string|in:caliber,color,quality',
            'value' => 'required|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['team_id'] = Auth::user()->team_id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $classification = FruitClassificationType::create($validated);

        return response()->json($classification, 201);
    }

    public function destroy(FruitClassificationType $fruitClassificationType)
    {
        $fruitClassificationType->delete();

        return response()->json(['message' => 'Eliminado correctamente.']);
    }
}
