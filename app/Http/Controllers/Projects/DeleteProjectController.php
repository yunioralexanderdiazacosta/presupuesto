<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;

class DeleteProjectController extends Controller
{
    public function __invoke(Project $project)
    {
        $project->delete();

        return redirect()->back()->with('success', 'Proyecto eliminado correctamente');
    }
}
