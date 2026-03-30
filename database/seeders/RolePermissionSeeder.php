<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $administrator = Role::firstOrCreate(['name' => 'Administrator']);
        $staffSupervisor = Role::firstOrCreate(['name' => 'Staff member - supervisor']);
        $researcher = Role::firstOrCreate(['name' => 'Researcher']);
        $supportColleague = Role::firstOrCreate(['name' => 'Support colleague']);

        // Create permissions
        $permissions = [
            'view projects',
            'create projects',
            'update projects',
            'delete projects',
            'manage tags',
            'manage users',
            'manage organizations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to Administrator
        $administrator->givePermissionTo(Permission::all());

        // Assign permissions to Staff member - supervisor
        $staffSupervisor->givePermissionTo([
            'view projects',
            'create projects',
            'update projects',
            'manage organizations',
        ]);

        // Assign permissions to Researcher
        $researcher->givePermissionTo([
            'view projects',
            'create projects',
            'update projects',
            'manage organizations',
        ]);

        // Assign permissions to Support colleague
        $supportColleague->givePermissionTo([
            'view projects',
            'create projects',
            'update projects',
            'delete projects',
            'manage organizations',
        ]);
    }
}
