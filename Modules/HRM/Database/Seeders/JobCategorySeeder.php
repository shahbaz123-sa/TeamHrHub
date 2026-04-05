<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\JobCategory;

class JobCategorySeeder extends Seeder
{
    public function run(): void
    {
        JobCategory::truncate();
        JobCategory::factory()->createMany([
            ['name' => 'Executive'],
            ['name' => 'Management'],
            ['name' => 'Professional'],
            ['name' => 'Technical'],
            ['name' => 'Administrative']
        ]);
    }
}
