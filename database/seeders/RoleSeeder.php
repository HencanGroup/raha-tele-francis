<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RoleSeeder
 * -----------------------------------------------------------------------------
 * Seeds the super_admin role only. The role name is pulled from
 * `config('filament-shield.super_admin.name')` and falls back to the literal
 * 'super_admin' if the config key is missing.
 *
 * Idempotent — uses Role::firstOrCreate(), so re-running is safe.
 *
 * Public entrypoint: run()
 * All business logic is delegated to the protected helpers below so the
 * run() method reads as a high-level orchestration script.
 */
class RoleSeeder extends Seeder
{
    /**
     * Guard the role is bound to. Filament panels all use the 'web' guard,
     * so Spatie roles must be created under the same guard to match.
     */
    protected const GUARD_NAME = 'web';

    /**
     * Orchestrates the seeding flow: resolve config → upsert role →
     * invalidate the Spatie permission cache so the change is visible on
     * the current request.
     */
    public function run(): void
    {
        // 1. Resolve the role name from the filament-shield config.
        $superAdminRoleName = $this->resolveSuperAdminRoleName();

        // 2. Idempotently create (or fetch) the role row.
        $this->upsertSuperAdminRole($superAdminRoleName);

        // 3. Flush Spatie's in-memory permission cache so any code running
        //    later in the same request/CLI invocation sees the new role.
        $this->forgetPermissionCache();
    }

    /**
     * Resolves the super_admin role name from config. Logs the resolved
     * value so it shows up in the application log for audit purposes.
     */
    protected function resolveSuperAdminRoleName(): string
    {
        $name = config('filament-shield.super_admin.name', 'super_admin');

        Log::info('RoleSeeder: resolved super_admin role name from config', ['name' => $name]);
        $this->command->info("  → Super admin role name: {$name}");

        return $name;
    }

    /**
     * Upserts the role row on the 'web' guard. Reports whether the row was
     * newly inserted or already existed.
     */
    protected function upsertSuperAdminRole(string $roleName): RoleContract
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => self::GUARD_NAME,
        ]);

        if ($role->wasRecentlyCreated) {
            Log::info('RoleSeeder: created role', ['name' => $roleName, 'guard' => self::GUARD_NAME]);
            $this->command->info("  + Created role → {$roleName} (guard: ".self::GUARD_NAME.')');
        } else {
            Log::info('RoleSeeder: role already exists, skipping insert', ['name' => $roleName]);
            $this->command->info("  ↻ Role already exists → {$roleName}");
        }

        return $role;
    }

    /**
     * Invalidates Spatie's cached permission/role lookups so subsequent
     * `assignRole()` / `hasRole()` calls in the same process see the new row.
     */
    protected function forgetPermissionCache(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info('RoleSeeder: flushed Spatie permission cache');
        $this->command->info('  ✓ Permission cache flushed');
    }
}
