<?php

namespace App\Http\Controllers\PhenologicalStages;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormPhenologicalStageRequest;
use App\Models\PhenologicalStage;

class StorePhenologicalStageController extends Controller
{
    public function __invoke(FormPhenologicalStageRequest $request)
    {
        $user = Auth::user();

        PhenologicalStage::create([
            'name' => $request->name,
            'fruit_id' => $request->fruit_id,
            'team_id' => $user->team_id
        ]);
    }
}
