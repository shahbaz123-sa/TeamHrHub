<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\PerformanceType;

class PerformanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        PerformanceType::truncate();
        PerformanceType::factory()->createMany([
            ['name' => 'Exceeds Expectations'],
            ['name' => 'Meets Expectations'],
            ['name' => 'Needs Improvement']
        ]);
    }
}
