<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamModulesRequest;
use App\Models\Team;
use App\Models\TeamDisabledModule;

class UpdateTeamModulesController extends Controller
{
    public function __invoke(Team $team, UpdateTeamModulesRequest $request)
    {
        $team->disabledModules()->delete();

        foreach ($request->input('disabled_modules', []) as $moduleKey) {
            TeamDisabledModule::create([
                'team_id' => $team->id,
                'module_key' => $moduleKey,
            ]);
        }

        return back()->with('success', 'Módulos actualizados correctamente');
    }
}
