<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ActiveInactiveUserController extends Controller
{
     public function __invoke(User $user, Request $request)
    {
        $request->validate([
            'status' => 'required',
        ]);

        $user->status = $request->status;
        $user->save();   
    }
}
