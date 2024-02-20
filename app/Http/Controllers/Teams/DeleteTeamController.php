<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DeleteTeamController extends Controller
{
    public function __invoke(User $user)
    {
        $user->team()->delete();

        $user->delete();
    }
}
