<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Shield handles permission generation; this seeder ensures super_admin
        // gets all existing permissions
        $superAdmin = Role::where('name', 'super_admin')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::all());
        }

        // Give admin all existing permissions too for backward compatibility
        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }
    }
}
