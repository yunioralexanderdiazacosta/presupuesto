<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSeasonRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;

class StoreSeasonController extends Controller
{
    public function __invoke(FormSeasonRequest $request)
    {
        $user = Auth::user();

        Season::Create([
            'name' => $request->name,
            'observations' => $request->observations,
            'month_id' => $request->month_id,
            'team_id' => $user->team_id
        ]);
    }
}
