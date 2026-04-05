<?php

namespace Modules\HRM\Database\Seeders;

use Modules\HRM\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::truncate();
        Branch::factory()->createMany([
            ['name' => 'Head Office', 'address' => 'New York, USA'],
            ['name' => 'Regional Office', 'address' => 'London, UK'],
            ['name' => 'Development Center', 'address' => 'Bangalore, India']
        ]);
    }
}
