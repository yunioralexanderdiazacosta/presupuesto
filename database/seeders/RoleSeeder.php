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

        $roleSuperAdmin = Role::create(['name' => 'Super Admin']);
        $roleAdmin      = Role::create(['name' => 'Admin']);
        $roleNormal     = Role::create(['name' => 'Normal']);

        $user = User::create([
            'name'                  => 'Super Admin',
            'email'                 => 'superadmin@example',
            'password'              => Hash::make('1234'),
        ]);

        $user->assignRole($roleSuperAdmin);
    }
}
