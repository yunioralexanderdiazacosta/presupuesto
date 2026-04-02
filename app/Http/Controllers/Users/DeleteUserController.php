<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DeleteUserController extends Controller
{
    public function __invoke(User $user)
    {
        $owner = \Illuminate\Support\Facades\Auth::user();

        // Impedir eliminar usuarios de otro team o Super Admins
        if ($user->team_id !== $owner->team_id && !$owner->hasRole('Super Admin')) {
            abort(403);
        }
        if ($user->hasRole('Super Admin') && !$owner->hasRole('Super Admin')) {
            abort(403);
        }

        $user->delete();
    }
}
