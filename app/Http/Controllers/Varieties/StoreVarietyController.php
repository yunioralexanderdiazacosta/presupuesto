<?php

namespace App\Http\Controllers\Varieties;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormVarietyRequest;
use App\Models\Variety;

class StoreVarietyController extends Controller
{
    public function __invoke(FormVarietyRequest $request)
    {
        $user = Auth::user();

        Variety::create([
            'name' => $request->name,
            'fruit_id' => $request->fruit_id,
            'observations' => $request->observations,
            'team_id' => $user->team_id
        ]);
    }
}
