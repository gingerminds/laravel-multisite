<?php

namespace Gingerminds\LaravelMultisite\Database\Seeders;

use Gingerminds\LaravelCore\Models\Permission\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::updateOrCreate(['name' => 'view sites', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'edit sites', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'delete sites', 'guard_name' => 'web']);

        Permission::updateOrCreate(['name' => 'view languages', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'edit languages', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'delete languages', 'guard_name' => 'web']);

        Permission::updateOrCreate(['name' => 'manage translations', 'guard_name' => 'web']);

        $this->command->info('Permissions table seeded!');
        // updateOrCreate roles and assign existing permissions
    }
}
