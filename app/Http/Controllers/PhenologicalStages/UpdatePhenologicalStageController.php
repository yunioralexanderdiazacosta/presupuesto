<?php

namespace App\Http\Controllers\PhenologicalStages;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormPhenologicalStageRequest;
use App\Models\PhenologicalStage;

class UpdatePhenologicalStageController extends Controller
{
    public function __invoke(PhenologicalStage $phenologicalStage, FormPhenologicalStageRequest $request)
    {
        $phenologicalStage->name = $request->name;
        $phenologicalStage->fruit_id = $request->fruit_id;
        $phenologicalStage->save();
    }
}
