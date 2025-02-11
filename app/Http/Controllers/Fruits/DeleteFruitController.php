<?php

namespace App\Http\Controllers\Fruits;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fruit;

class DeleteFruitController extends Controller
{
    public function __invoke(Fruit $fruit)
    {
        $fruit->delete();
    }
}
