<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Support\ModuleAccess;

class TeamModulesController extends Controller
{
    public function __invoke(Team $team)
    {
        return response()->json([
            'catalog' => ModuleAccess::catalog(),
            'disabled_modules' => $team->disabledModules()->pluck('module_key')->values(),
        ]);
    }
}
