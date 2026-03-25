<?php

namespace App\Http\Controllers\ProjectEvaluations;

use App\Http\Controllers\Controller;
use App\Models\ProjectEvaluation;
use App\Models\Variety;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectEvaluationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $evaluations = ProjectEvaluation::where('team_id', $user->team_id)
            ->withCount('rows')
            ->orderByDesc('created_at')
            ->get();

        $varieties = Variety::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('ProjectEvaluations/Index', [
            'evaluations' => $evaluations,
            'varieties'   => $varieties,
        ]);
    }
}
