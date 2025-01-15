<?php

namespace App\Http\Controllers\Level3s;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level2;

class GetLevel3Controller extends Controller
{
    public function __invoke(Level2 $level2)
    {
        return response()->json($level2->level3s()->orderBy('name', 'asc')->get()->transform(function ($value){
            return [
                'label' => $value->name,
                'value' => $value->id
            ];
        })); 
    }
}
