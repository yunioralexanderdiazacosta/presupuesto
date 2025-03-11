<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSeasonRequest;
use App\Models\Season;

class UpdateSeasonController extends Controller
{
    public function __invoke(Season $season, FormSeasonRequest $request)
    {
        $season->name = $request->name;
        $season->observations = $request->observations;
        $season->month_id = $request->month_id;
        $season->save();
    }
}
