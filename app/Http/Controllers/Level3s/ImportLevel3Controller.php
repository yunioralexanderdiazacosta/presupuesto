<?php

namespace App\Http\Controllers\Level3s;

use App\Http\Controllers\Controller;
use App\Models\Level2;
use App\Models\Level3;
use App\Models\ImportLevel;
use Illuminate\Support\Facades\Auth;

class ImportLevel3Controller extends Controller
{
    public function __invoke(Level2 $level2)
    {
        $user = Auth::user();

        $level = Level2::select('id')->whereHas('level1', function($query) use ($user){
            $query->where('season_id', 1);
        })->where('name', $level2->name)->first();

        $level3s = Level3::where('level2_id', $level->id)->get();

        foreach($level3s as $level3){
            Level3::create([
                'level2_id' => $level2->id,
                'name' => $level3->name
            ]);
        }

        ImportLevel::create([
            'user_id'   => $user->id,
            'level2_id' => $level2->id
        ]);
    }
}
