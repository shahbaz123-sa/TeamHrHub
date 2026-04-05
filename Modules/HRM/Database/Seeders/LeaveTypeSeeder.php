<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        LeaveType::truncate();
        LeaveType::factory()->createMany([
            ['name' => 'Annual Leave', 'quota' => 14],
            ['name' => 'Sick Leave', 'quota' => 8],
            ['name' => 'Maternity Leave', 'quota' => 90],
            ['name' => 'Paternity Leave', 'quota' => 14],
            ['name' => 'Casual Leave', 'quota' => 10],
            ['name' => 'Half Day Leave', 'quota' => 6]
        ]);
    }
}
