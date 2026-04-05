<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\GoalType;

class GoalTypeSeeder extends Seeder
{
    public function run(): void
    {
        GoalType::truncate();
        GoalType::factory()->createMany([
            ['name' => 'Individual Performance'],
            ['name' => 'Team Performance'],
            ['name' => 'Departmental Objective'],
            ['name' => 'Company Goal']
        ]);
    }
}
