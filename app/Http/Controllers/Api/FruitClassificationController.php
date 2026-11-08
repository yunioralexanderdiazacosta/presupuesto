<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FruitClassificationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FruitClassificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $classifications = FruitClassificationType::where('team_id', $user->team_id)
            ->when($request->fruit_id, fn($q) => $q->where('fruit_id', $request->fruit_id))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json($classifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fruit_id' => ['required', Rule::exists('fruits', 'id')->where('team_id', Auth::user()->team_id)],
            'type' => 'required|string|in:caliber,color,quality',
            'value' => 'required|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['team_id'] = Auth::user()->team_id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $classification = FruitClassificationType::create($validated);

        return response()->json($classification, 201);
    }

    /** Reasigna sort_order según el orden de ids recibido (0, 1, 2...) para un frutal y tipo. */
    public function reorder(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'fruit_id' => ['required', Rule::exists('fruits', 'id')->where('team_id', $user->team_id)],
            'type' => 'required|string|in:caliber,color,quality',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $items = FruitClassificationType::where('team_id', $user->team_id)
            ->where('fruit_id', $validated['fruit_id'])
            ->where('type', $validated['type'])
            ->whereIn('id', $validated['ids'])
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($validated, $items) {
            foreach ($validated['ids'] as $position => $id) {
                $items->get($id)?->update(['sort_order' => $position]);
            }
        });

        return response()->json(['message' => 'Orden actualizado.']);
    }

    public function destroy(FruitClassificationType $fruitClassificationType)
    {
        if ($fruitClassificationType->team_id !== Auth::user()->team_id) {
            abort(403);
        }

        $fruitClassificationType->delete();

        return response()->json(['message' => 'Eliminado correctamente.']);
    }
}
