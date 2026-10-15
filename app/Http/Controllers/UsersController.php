<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Support\ModuleAccess;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class UsersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $users = User::where('team_id', $user->team_id)->when($request->term, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('username', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%');
            });
        })
        ->paginate(10)
        ->withQueryString()
        ->through(function($value) use ($user) {
            return [
                'id' => $value->id,
                'name' => $value->name,
                'username' => $value->username,
                'email' => $value->email,
                'roles' => $value->getRoleNames()->toArray(),
                'status' => $value->status,
                'created_at' => $value->created_at,
                'restrict_modules' => (bool) $value->restrict_modules,
                'allowed_modules' => $value->allowedModuleKeys(),
                'can_manage_modules' => ModuleAccess::canManageModules($user, $value),
            ];
        });

        // Obtener roles disponibles (Super Admin solo visible para Super Admin)
        $availableRoles = Role::orderBy('name')
            ->when(!$user->hasRole('Super Admin'), function($q) {
                $q->where('name', '!=', 'Super Admin');
            })
            ->get()->map(function($role) {
                return [
                    'value' => $role->name,
                    'label' => $role->name == 'Normal' ? 'Digitador' : $role->name
                ];
            });

        // Módulos que se pueden asignar (agrupados como en el menú), sin los bloqueados para la empresa
        $moduleGroups = ModuleAccess::groupedCatalog(
            $user->team ? $user->team->disabledModules()->pluck('module_key')->all() : []
        );

        return Inertia::render('Users', compact('users', 'term', 'availableRoles', 'moduleGroups'));
    }
}
