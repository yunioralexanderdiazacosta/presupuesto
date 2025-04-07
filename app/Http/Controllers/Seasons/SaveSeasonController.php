<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SaveSeasonController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'season_id' => 'required'
        ]);

        session(['season_id' => $request->season_id]);
    }
}
