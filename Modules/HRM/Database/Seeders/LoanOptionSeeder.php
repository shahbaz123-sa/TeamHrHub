<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\LoanOption;

class LoanOptionSeeder extends Seeder
{
    public function run(): void
    {
        LoanOption::truncate();
        LoanOption::factory()->createMany([
            ['name' => 'Emergency Loan'],
            ['name' => 'Housing Loan'],
            ['name' => 'Vehicle Loan'],
            ['name' => 'Education Loan'],
            ['name' => 'Medical Loan']
        ]);
    }
}
