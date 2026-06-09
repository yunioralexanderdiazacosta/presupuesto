<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormProjectRequest;
use App\Models\Project;

use App\Traits\CheckSeasonLocked;

class UpdateProjectController extends Controller
{
    public function __invoke(FormProjectRequest $request, Project $project)
    {
        $project->update([
            'name'         => $request->name,
            'date'         => $request->date,
            'observations' => $request->observations,
            'budget'       => $request->budget,
            'operation_id' => $request->operation_id,
        ]);

        return redirect()->back()->with('success', 'Proyecto actualizado correctamente');
    }
}
