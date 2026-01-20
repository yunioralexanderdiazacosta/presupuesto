<?php

namespace App\Http\Controllers\PhenologicalStages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhenologicalStage;

class DeletePhenologicalStageController extends Controller
{
    public function __invoke(PhenologicalStage $phenologicalStage)
    {
        $phenologicalStage->delete();
    }
}
