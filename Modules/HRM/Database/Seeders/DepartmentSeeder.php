<?php

namespace Modules\HRM\Database\Seeders;

use Modules\HRM\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::truncate();
        Department::factory()->createMany([
            ['name' => 'Executive Management'],
            ['name' => 'Human Resources'],
            ['name' => 'Finance'],
            ['name' => 'Engineering'],
            ['name' => 'Marketing']
        ]);
    }
}
