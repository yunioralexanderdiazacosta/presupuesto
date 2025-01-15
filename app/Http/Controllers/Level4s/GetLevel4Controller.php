<?php

namespace App\Http\Controllers\Level4s;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level3;

class GetLevel4Controller extends Controller
{
      public function __invoke(Level3 $level3)
    {
        return response()->json($level3->level4s()->orderBy('name', 'asc')->get()->transform(function ($value){
            return [
                'label' => $value->name,
                'value' => $value->id
            ];
        })); 
    }
}
