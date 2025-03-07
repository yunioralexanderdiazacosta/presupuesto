<?php

namespace App\Http\Controllers\Varieties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Fruit;

class GetVarietyController extends Controller
{
    public function __invoke(Fruit $fruit)
    {
          return response()->json($fruit->varieties()->orderBy('name', 'asc')->get()->transform(function ($value){
            return [
                'label' => $value->name,
                'value' => $value->id
            ];
        })); 
    }
}
