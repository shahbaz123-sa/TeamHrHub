<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\EmploymentStatus;

class EmploymentStatusSeeder extends Seeder
{
    public function run()
    {
        EmploymentStatus::truncate();
        EmploymentStatus::factory()->createMany([
            ['name' => 'Active'],
            ['name' => 'On Leave'],
            ['name' => 'Terminated'],
            ['name' => 'Suspended'],
        ]);
    }
}
