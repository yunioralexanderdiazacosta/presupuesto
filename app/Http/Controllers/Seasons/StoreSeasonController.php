<?php

namespace App\Http\Controllers\Seasons;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSeasonRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\Level1;
use App\Models\Level2;
use App\Models\Level1Template;
use App\Models\Level2Template;

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

        // Si no hay plantillas definidas, copiar niveles desde la temporada base (id=1)
        $level1Templates = Level1Template::orderBy('order')->get();
        if ($level1Templates->isEmpty()) {
            $baseLevels = Level1::where('season_id', 1)->get();
            foreach ($baseLevels as $level) {
                $level1 = Level1::create([
                    'season_id' => $season->id,
                    'team_id'   => $user->team_id,
                    'name'      => $level->name,
                ]);
                $sublevels = Level2::where('level1_id', $level->id)->get();
                foreach ($sublevels as $sub) {
                    Level2::create([
                        'level1_id' => $level1->id,
                        'name'      => $sub->name,
                    ]);
                }
            }
        } else {
            // Copiado desde plantillas
            foreach ($level1Templates as $template1) {
                $level1 = Level1::create([
                    'season_id' => $season->id,
                    'team_id'   => $user->team_id,
                    'name'      => $template1->name,
                ]);
                foreach ($template1->level2Templates()->orderBy('order')->get() as $template2) {
                    Level2::create([
                        'level1_id' => $level1->id,
                        'name'      => $template2->name,
                    ]);
                }
            }
        }
        // Redirigir al listado de temporadas
        return redirect()->route('seasons.index');
    }
}
