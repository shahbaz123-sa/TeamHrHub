<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = ['admin', 'author', 'editor', 'maintainer', 'subscriber'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Get admin role
        $adminRole = Role::where('name', 'admin')->first();

        // Define and assign permissions
        $permissions = [
            'create-user',
            'edit-user',
            'delete-user',
            'view-users',
            'manage-roles',
            'manage-plans'
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm]);
            if (!$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }

        // Create admin user
        User::factory()
            ->admin()
            ->active()
            ->enterprise()
            ->create([
                'name' => 'Admin User',
                'email' => 'admin@zarea.com',
                'password' => bcrypt('password'),
            ])->assignRole($adminRole);

        // Create regular users
        User::factory()
            ->count(50)
            ->create()
            ->each(function ($user) {
                $user->assignRole(
                    Role::where('name', $user->role)->first()
                );
            });

        // Create some inactive users
        User::factory()
            ->count(5)
            ->state(['status' => 'inactive'])
            ->create()
            ->each(function ($user) {
                $user->assignRole(
                    Role::where('name', $user->role)->first()
                );
            });

        // Create some pending users
        User::factory()
            ->count(5)
            ->state(['status' => 'pending'])
            ->create()
            ->each(function ($user) {
                $user->assignRole(
                    Role::where('name', $user->role)->first()
                );
            });
    }
}
