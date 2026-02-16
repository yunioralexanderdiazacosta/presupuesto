<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class UsersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $users = User::where('team_id', $user->team_id)->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
        })
        ->paginate(10)
        ->withQueryString()
        ->through(function($value){
            return [
                'id' => $value->id,
                'name' => $value->name,
                'email' => $value->email,
                'roles' => $value->getRoleNames()->toArray(),
                'status' => $value->status,
                'created_at' => $value->created_at 
            ];
        });

        // Obtener todos los roles disponibles
        $availableRoles = Role::orderBy('name')->get()->map(function($role) {
            return [
                'value' => $role->name,
                'label' => $role->name == 'Normal' ? 'Digitador' : $role->name
            ];
        });

        return Inertia::render('Users', compact('users', 'term', 'availableRoles'));
    }
}
