<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;


class TeamsController extends Controller
{
    public function __invoke()
    {
        $teams = User::role('Admin')->with('team')->paginate(10);

        return Inertia::render('Teams', compact('teams'));
    }
}
