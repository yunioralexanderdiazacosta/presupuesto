<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use Inertia\Inertia;

class SelectBudgetController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        abort_if(
            !$user->hasRole('Admin') && !$user->hasRole('Super Admin'),
            403,
            'Solo los administradores pueden cambiar la temporada.'
        );

        $seasons = Season::select('id', 'name')->where('team_id', $user->team_id)->get()->transform(function($season){
            return [
                'label' => $season->name,
                'value' => $season->id
            ];
        });

        return Inertia::render('SelectBudget', compact('seasons'));
    }
}
