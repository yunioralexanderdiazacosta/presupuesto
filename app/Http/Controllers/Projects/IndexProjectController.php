<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Operation;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IndexProjectController extends Controller
{
    public function __invoke()
    {
        $user      = Auth::user();
        $season_id = session('season_id');

        $projects = Project::with('operation')
            ->where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'date'         => $p->date,
                'observations' => $p->observations,
                'budget'       => $p->budget,
                'operation_id' => $p->operation_id,
                'operation'    => $p->operation ? ['id' => $p->operation->id, 'name' => $p->operation->name] : null,
                'user_id'      => $p->user_id,
            ]);

        $operations = Operation::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Projects/Index', [
            'projects'   => $projects,
            'operations' => $operations,
        ]);
    }
}
