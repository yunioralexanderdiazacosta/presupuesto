<?php

namespace App\Http\Controllers\Varieties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Fruit;

class GetVarietyController extends Controller
{
    public function __invoke(Fruit $fruit)
    {
        $user = Auth::user();
        
        return response()->json($fruit->varieties()
            ->where('team_id', $user->team_id)
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get()
            ->transform(function ($value) {
                return [
                    'label' => $value->name,
                    'value' => $value->id
                ];
            })); 
    }
}
