<?php

namespace Modules\HRM\Database\Seeders;

use Database\Seeders\AssetAssignmentHistorySeeder;
use Illuminate\Database\Seeder;
use Modules\Auth\Database\Seeders\AdminUserSeeder;
use Modules\Auth\Database\Seeders\RolesAndPermissionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RolesAndPermissionSeeder::class,

            HRMDatabaseSeeder::class,

            AssetAssignmentHistorySeeder::class,
        ]);
    }
}
