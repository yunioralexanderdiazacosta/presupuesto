<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class StoreProjectController extends Controller
{
    public function __invoke(FormProjectRequest $request)
    {
        $user = Auth::user();

        Project::create([
            'name'         => $request->name,
            'date'         => $request->date,
            'observations' => $request->observations,
            'budget'       => $request->budget,
            'operation_id' => $request->operation_id,
            'season_id'    => session('season_id'),
            'team_id'      => $user->team_id,
            'user_id'      => $user->id,
        ]);

        return redirect()->back()->with('success', 'Proyecto creado correctamente');
    }
}
