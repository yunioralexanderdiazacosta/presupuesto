<?php

namespace App\Http\Controllers\Level2s;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level1;

class GetLevel2Controller extends Controller
{
    public function __invoke(Level1 $level1)
    {
        return response()->json($level1->levels2()->orderBy('name', 'asc')->get()->transform(function ($value){
            return [
                'label' => $value->name,
                'value' => $value->id
            ];
        })); 
    }
}
