<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\Allowance;

class AllowanceSeeder extends Seeder
{
    public function run(): void
    {
        Allowance::truncate();
        Allowance::factory()->createMany([
            ['name' => 'Housing Allowance'],
            ['name' => 'Transport Allowance'],
            ['name' => 'Meal Allowance'],
            ['name' => 'Phone Allowance'],
            ['name' => 'Education Allowance']
        ]);
    }
}
