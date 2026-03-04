<?php

namespace App\Http\Controllers\Operators;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormOperatorRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Operator;

class StoreOperatorController extends Controller
{
    public function __invoke(FormOperatorRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        Operator::create([
            'name'      => $request->name,
            'position'  => $request->position,
            'team_id'   => $user->team_id,
            'season_id' => $season_id,
        ]);
    }
}
