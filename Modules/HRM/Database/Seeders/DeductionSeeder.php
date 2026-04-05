<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\Deduction;

class DeductionSeeder extends Seeder
{
    public function run(): void
    {
        Deduction::truncate();
        Deduction::factory()->createMany([
            ['name' => 'Income Tax'],
            ['name' => 'Social Security'],
            ['name' => 'Health Insurance'],
            ['name' => 'Retirement Plan'],
            ['name' => 'Union Dues']
        ]);
    }
}
