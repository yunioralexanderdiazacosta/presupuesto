<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ActivateInactivateTeamController extends Controller
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
