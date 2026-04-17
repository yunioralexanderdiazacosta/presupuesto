<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TogglePermissionController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('Super Admin')) {
            abort(403);
        }

        $request->validate([
            'permission' => 'required|string',
            'role_id' => 'required|integer',
            'enabled' => 'required|boolean',
        ]);

        $role = Role::findOrFail($request->role_id);
        $permission = Permission::where('name', $request->permission)->firstOrFail();

        if ($request->enabled) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        // Limpiar cache de permisos de Spatie
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Permiso '{$permission->name}' " . ($request->enabled ? 'activado' : 'desactivado') . " para {$role->name}");
    }
}
