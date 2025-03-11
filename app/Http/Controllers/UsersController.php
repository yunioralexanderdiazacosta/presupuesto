<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Inertia\Inertia;

class UsersController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $users = User::where('team_id', $user->team_id)->whereNot('id', $user->id)->paginate(10)->through(function($value){
            return [
                'id' => $value->id,
                'name' => $value->name,
                'email' => $value->email,
                'role'  => $value->getRoleNames()[0] == 'Normal' ? 'Digitador' : 'Administrador',
                'status' => $value->status,
                'created_at' => $value->created_at 
            ];
        });

        return Inertia::render('Users', compact('users'));
    }
}
