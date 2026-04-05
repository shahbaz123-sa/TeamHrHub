<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\EmploymentType;

class EmploymentTypeSeeder extends Seeder
{
    public function run()
    {
        EmploymentType::truncate();
        EmploymentType::factory()->createMany([
            ['name' => 'Full Time'],
            ['name' => 'Part Time'],
            ['name' => 'Contract'],
            ['name' => 'Temporary'],
        ]);
    }
}
