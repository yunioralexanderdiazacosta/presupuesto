<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamRequest;
use App\Models\User;

class UpdateTeamController extends Controller
{
    public function __invoke(User $user, UpdateTeamRequest $request)
    {
        $user->name = $request->name;
        $user->email = $request->email;
        if($request->password != ''){
            $user->password = Hash::make($request->password);
        }
        $user->observations = $request->observations;
        $user->save();

        $user->team->name = $request->team_name;
        $user->team->save(); 
    }
}
