<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UpdateTeamController extends Controller
{
    public function __invoke(User $user, UpdateTeamRequest $request)
    {
        $owner = Auth::user();

        if ($user->team_id !== $owner->team_id && !$owner->hasRole('Super Admin')) {
            abort(403);
        }
        if ($user->hasRole('Super Admin') && !$owner->hasRole('Super Admin')) {
            abort(403);
        }

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        if($request->password != ''){
            $user->password = Hash::make($request->password);
        }
        $user->observations = $request->observations;
        $user->save();

        $user->team->name = $request->team_name;
        $user->team->user_id = $user->id;
        $user->team->save(); 
    }
}
