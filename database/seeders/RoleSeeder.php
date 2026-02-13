<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roleSuperAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'Admin']);
        $roleNormal     = Role::firstOrCreate(['name' => 'Normal']);
        $roleAprobador  = Role::firstOrCreate(['name' => 'Aprobador Compras']);

        // Solo crear usuario admin si no existe
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('1234'),
            ]
        );

        if (!$user->hasRole('Super Admin')) {
            $user->assignRole($roleSuperAdmin);
        }
    }
}
