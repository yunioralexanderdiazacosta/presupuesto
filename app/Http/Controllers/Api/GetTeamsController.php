<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class GetTeamsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return Team::where('id', '!=', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($team) => ['value' => $team->id, 'label' => $team->name]);
    }
}
