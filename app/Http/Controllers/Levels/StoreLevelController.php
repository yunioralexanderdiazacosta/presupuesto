<?php

namespace App\Http\Controllers\Levels;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormLevelRequest;
use App\Models\Level1;

class StoreLevelController extends Controller
{
    public function __invoke(FormLevelRequest $request)
    {
        $user = Auth::user();

        Level1::create([
            'name' => $request->name,
            'team_id' => $user->team_id
        ]);
    }
}
