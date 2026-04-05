<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\JobStage;

class JobStageSeeder extends Seeder
{
    public function run(): void
    {
        JobStage::truncate();
        JobStage::factory()->createMany([
            ['name' => 'Application Received'],
            ['name' => 'Interview'],
            ['name' => 'Offer Sent'],
            ['name' => 'Hired']
        ]);
    }
}
