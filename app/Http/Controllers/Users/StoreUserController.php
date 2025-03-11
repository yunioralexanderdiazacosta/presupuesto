<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Users\StoreUserRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StoreUserController extends Controller
{
    public function __invoke(StoreUserRequest $request)
    {
        $owner = Auth::user();

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->observations = $request->observations;
        $user->team_id = $owner->team_id;
        $user->save();

        $user->assignRole($request->role);
    }
}
