<?php

namespace Modules\HRM\Database\Seeders;

use Modules\HRM\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        Designation::truncate();
        Designation::factory()->createMany([
            ['title' => 'Chief Executive Officer'],
            ['title' => 'Chief Technology Officer'],
            ['title' => 'HR Manager'],
            ['title' => 'Senior Software Engineer'],
            ['title' => 'Software Engineer'],
            ['title' => 'Finance Manager'],
            ['title' => 'Marketing Specialist']
        ]);
    }
}
