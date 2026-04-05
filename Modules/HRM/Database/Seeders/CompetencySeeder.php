<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\Competency;

class CompetencySeeder extends Seeder
{
    public function run(): void
    {
        Competency::truncate();
        Competency::factory()->createMany([
            ['name' => 'Technical Expertise'],
            ['name' => 'Communication'],
            ['name' => 'Teamwork'],
            ['name' => 'Problem Solving']
        ]);
    }
}
