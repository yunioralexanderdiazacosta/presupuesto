<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserModule;
use App\Support\ModuleAccess;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    private function restrictedUser(array $keys, bool $restricted = true): User
    {
        $user = new User();
        $user->restrict_modules = $restricted;
        $user->setRelation('modules', collect($keys)->map(fn ($key) => new UserModule(['module_key' => $key])));

        return $user;
    }

    public function test_module_matches_its_family_of_routes(): void
    {
        $this->assertTrue(ModuleAccess::matches('payroll-reports.index', 'payroll-reports.pdf'));
        $this->assertTrue(ModuleAccess::matches('daily-management.index', 'daily-yields.store'));
        $this->assertTrue(ModuleAccess::matches('payroll-dashboard', 'payroll-dashboard'));
        $this->assertFalse(ModuleAccess::matches('payroll-reports.index', 'employees.index'));
    }

    public function test_sibling_module_keys_do_not_overlap(): void
    {
        // 'outflows.dashboard' es otro módulo distinto de 'outflows.index'
        $this->assertFalse(ModuleAccess::matches('outflows.index', 'outflows.dashboard'));
        $this->assertTrue(ModuleAccess::matches('outflows.index', 'outflows.store'));
        $this->assertTrue(ModuleAccess::matches('outflows.dashboard', 'outflows.dashboard'));
        $this->assertFalse(ModuleAccess::matches('outflows.dashboard', 'outflows.store'));
    }

    public function test_unrestricted_user_is_never_blocked_by_user_rules(): void
    {
        $user = $this->restrictedUser([], false);

        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'payroll-reports.index'));
    }

    public function test_restricted_user_only_reaches_allowed_modules_and_basics(): void
    {
        $user = $this->restrictedUser(['expense-reports.index']);

        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'expense-reports.show'));
        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'api.suppliers.store'));
        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'invoices.search'));
        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'home'));
        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'profile.show'));

        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, 'invoices.index'));
        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, 'payroll-reports.index'));
        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, 'users.index'));
        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, null));
    }

    public function test_restricting_all_but_payroll_hides_only_payroll(): void
    {
        $keys = collect(ModuleAccess::catalog())
            ->reject(fn ($m) => $m['group'] === 'Remuneraciones')
            ->pluck('key')
            ->all();
        $user = $this->restrictedUser($keys);

        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, 'payroll-reports.pdf'));
        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, 'employees.store'));
        $this->assertTrue(ModuleAccess::isBlockedForRestrictedUser($user, 'daily-yields.index'));
        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'invoices.index'));
        $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, 'outflows.dashboard'));
    }

    public function test_every_catalog_module_reaches_its_own_key_route(): void
    {
        foreach (ModuleAccess::catalogKeys() as $key) {
            $user = $this->restrictedUser([$key]);

            $this->assertFalse(ModuleAccess::isBlockedForRestrictedUser($user, $key), "El módulo $key no cubre su propia ruta");
        }
    }

    public function test_every_catalog_module_is_assigned_to_a_menu_group(): void
    {
        foreach (ModuleAccess::catalog() as $module) {
            $this->assertContains($module['group'], array_keys(ModuleAccess::GROUPS), "{$module['key']} no tiene grupo de menú");
        }
    }
}
