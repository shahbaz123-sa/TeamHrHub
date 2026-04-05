<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\PayslipType;

class PayslipTypeSeeder extends Seeder
{
    public function run(): void
    {
        PayslipType::truncate();
        PayslipType::factory()->createMany([
            ['name' => 'Monthly Salary'],
            ['name' => 'Bonus Payment'],
            ['name' => 'Overtime Payment']
        ]);
    }
}
