<?php

namespace App\Http\Controllers\Fruits;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormFruitRequest;
use App\Models\Fruit;

class UpdateFruitController extends Controller
{
    public function __invoke(Fruit $fruit, FormFruitRequest $request)
    {
        $fruit->name = $request->name;
        $fruit->save();
    }
}
