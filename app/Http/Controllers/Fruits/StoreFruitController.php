<?php

namespace App\Http\Controllers\Fruits;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormFruitRequest;
use App\Models\Fruit;

class StoreFruitController extends Controller
{
    public function __invoke(FormFruitRequest $request)
    {
        $user = Auth::user();

        Fruit::create([
            'name' => $request->name,
            'team_id' => $user->team_id
        ]);
    }
}
