<?php

namespace App\Http\Controllers\ProductionAnalysis;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductionAnalysis\SaveClassificationGroupsRequest;
use App\Models\ClassificationGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaveClassificationGroupsController extends Controller
{
    /** Reemplaza todos los grupos de un frutal y tipo de clasificación del equipo. */
    public function __invoke(SaveClassificationGroupsRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            ClassificationGroup::where('team_id', $user->team_id)
                ->where('fruit_id', $data['fruit_id'])
                ->where('classification_type', $data['classification_type'])
                ->delete();

            foreach ($data['groups'] as $index => $group) {
                ClassificationGroup::create([
                    'team_id' => $user->team_id,
                    'fruit_id' => $data['fruit_id'],
                    'classification_type' => $data['classification_type'],
                    'name' => $group['name'],
                    'values' => array_values($group['values']),
                    'sort_order' => $index,
                ]);
            }
        });

        return back()->with('success', 'Grupos guardados.');
    }
}
