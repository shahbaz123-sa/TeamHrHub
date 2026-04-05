<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\AwardType;

class AwardTypeSeeder extends Seeder
{
    public function run(): void
    {
        AwardType::truncate();
        AwardType::factory()->createMany([
            ['name' => 'Employee of the Month'],
            ['name' => 'Innovation Award'],
            ['name' => 'Long Service Award']
        ]);
    }
}
