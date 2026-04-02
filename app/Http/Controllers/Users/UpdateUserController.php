<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UpdateUserController extends Controller
{
    public function __invoke(User $user, UpdateUserRequest $request)
    {
        $owner = \Illuminate\Support\Facades\Auth::user();

        // Impedir editar usuarios de otro team o Super Admins (salvo el propio Super Admin)
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
        $user->save();

        $user->syncRoles($request->roles); 
    }
}
