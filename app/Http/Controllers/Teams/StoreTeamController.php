<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\Team;
use App\Models\User;

class StoreTeamController extends Controller
{
    public function __invoke(StoreTeamRequest $request)
    {
        $team = Team::create([
            'name' => $request->team_name
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->observations = $request->observations;
        $user->team_id = $team->id;
        $user->save();

    $team->user_id = $user->id;
    $team->save();

        $user->assignRole('Admin');
    }
}
