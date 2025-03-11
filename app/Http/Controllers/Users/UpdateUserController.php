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
        $user->name = $request->name;
        $user->email = $request->email;
        if($request->password != ''){
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $user->syncRoles([$request->role]); 
    }
}
