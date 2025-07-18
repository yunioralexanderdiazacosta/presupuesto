<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSeasonRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\Level1;
use App\Models\Level2;
use App\Models\Level3;
use App\Models\Level4;

class StoreSeasonController extends Controller
{
    public function __invoke(FormSeasonRequest $request)
    {
        $user = Auth::user();

        $season = Season::Create([
            'name' => $request->name,
            'observations' => $request->observations,
            'month_id' => $request->month_id,
            'team_id' => $user->team_id
        ]);

        $levels = Level1::where('season_id', 1)->get();

        foreach($levels as $level){

            $level1 = Level1::create([
                'season_id' => $season->id,
                'team_id'   => $user->team_id,
                'name'      => $level->name
            ]);

            $sublevel2s = Level2::where('level1_id', $level->id)->get();

            foreach($sublevel2s as $sublevel2){
                $level2 = Level2::create([
                    'level1_id' => $level1->id,
                    'name'      => $sublevel2->name
                ]);

                $sublevel3s = Level3::where('level2_id', $sublevel2->id)->get();

                foreach($sublevel3s as $sublevel3){
                    $level3 = Level3::create([
                        'level2_id' => $level2->id,
                        'name'      => $sublevel3->name
                    ]);

                    $sublevel4s = Level4::where('level3_id', $sublevel3->id)->get();

                    foreach($sublevel4s as $sublevel4){
                        $level4 = Level4::create([
                            'level3_id' => $level3->id,
                            'name'      => $sublevel4->name
                        ]);
                    }
                }
            }
        }
    }
}
