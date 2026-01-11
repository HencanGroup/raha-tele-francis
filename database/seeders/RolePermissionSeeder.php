<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Admin role gets all permissions
        $admin = Role::where('name', 'admin')->first();
        $admin->givePermissionTo(Permission::all());
    }
}
