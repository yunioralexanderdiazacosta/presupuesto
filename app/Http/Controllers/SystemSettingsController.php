<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemSettingsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('Super Admin')) {
            abort(403);
        }

        // Obtener permisos opcionales del sistema
        $optionalPermissions = [
            ['name' => 'copy-products', 'label' => 'Copiar productos entre equipos', 'description' => 'Permite copiar la base de datos de productos desde otro equipo'],
        ];

        // Roles asignables (excluir Super Admin)
        $roles = Role::where('name', '!=', 'Super Admin')->get();

        // Construir la matriz de permisos por rol
        $permissionMatrix = [];
        foreach ($optionalPermissions as $perm) {
            $permission = Permission::where('name', $perm['name'])->first();
            $roleStates = [];
            foreach ($roles as $role) {
                $roleStates[] = [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'enabled' => $permission ? $role->hasPermissionTo($perm['name']) : false,
                ];
            }
            $permissionMatrix[] = [
                'name' => $perm['name'],
                'label' => $perm['label'],
                'description' => $perm['description'],
                'roles' => $roleStates,
            ];
        }

        return Inertia::render('SystemSettings', [
            'permissionMatrix' => $permissionMatrix,
        ]);
    }
}
