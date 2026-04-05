<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\TrainingType;

class TrainingTypeSeeder extends Seeder
{
    public function run(): void
    {
        TrainingType::truncate();
        TrainingType::factory()->createMany([
            ['name' => 'Technical Skills'],
            ['name' => 'Leadership Development'],
            ['name' => 'Compliance Training'],
            ['name' => 'Soft Skills']
        ]);
    }
}
