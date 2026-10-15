<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Acceso por módulos a nivel de usuario. Si users.restrict_modules es true, el usuario
 * solo accede a los módulos listados en user_modules (más las rutas básicas del sistema).
 *
 * Reemplaza a los roles restrictivos "Rendidor" y "Ocultar Remuneraciones":
 * los usuarios que los tenían se migran a una restricción equivalente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('restrict_modules')->default(false);
        });

        Schema::create('user_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');
            $table->timestamps();

            $table->unique(['user_id', 'module_key']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $ocultarRole = 'Ocultar Remuneraciones';
        $existingRoles = Role::where('guard_name', 'web')->pluck('name');

        // Rendidor (único rol, ignorando "Ocultar Remuneraciones") => solo Rendiciones de Gastos
        $rendidores = $existingRoles->contains('Rendidor') ? User::role('Rendidor')->get() : collect();
        foreach ($rendidores as $user) {
            $roles = $user->getRoleNames()->reject(fn ($r) => $r === $ocultarRole);

            if ($roles->count() === 1) {
                $this->restrict($user->id, ['expense-reports.index']);
            }
        }

        // Ocultar Remuneraciones => todos los módulos excepto Remuneraciones
        $allExceptPayroll = collect(config('modules', []))
            ->reject(fn ($module) => $module['section'] === 'Remuneraciones')
            ->pluck('key')
            ->all();

        $ocultos = $existingRoles->contains($ocultarRole) ? User::role($ocultarRole)->get() : collect();
        foreach ($ocultos as $user) {
            if (!DB::table('users')->where('id', $user->id)->value('restrict_modules')) {
                $this->restrict($user->id, $allExceptPayroll);
            }
        }

        Role::whereIn('name', ['Rendidor', $ocultarRole])->where('guard_name', 'web')->get()->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_modules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('restrict_modules');
        });
    }

    private function restrict(int $userId, array $keys): void
    {
        DB::table('users')->where('id', $userId)->update(['restrict_modules' => true]);

        foreach ($keys as $key) {
            DB::table('user_modules')->insertOrIgnore([
                'user_id' => $userId,
                'module_key' => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
