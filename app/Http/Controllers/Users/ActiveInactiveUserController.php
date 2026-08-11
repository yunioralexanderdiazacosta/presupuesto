<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ActiveInactiveUserController extends Controller
{
     public function __invoke(User $user, Request $request)
    {
        $owner = Auth::user();

        // Impedir activar/suspender usuarios de otro team o Super Admins (salvo el propio Super Admin)
        if ($user->team_id !== $owner->team_id && !$owner->hasRole('Super Admin')) {
            abort(403);
        }
        if ($user->hasRole('Super Admin') && !$owner->hasRole('Super Admin')) {
            abort(403);
        }

        $request->validate([
            'status' => 'required',
        ]);

        $user->status = $request->status;
        $user->save();   
    }
}
