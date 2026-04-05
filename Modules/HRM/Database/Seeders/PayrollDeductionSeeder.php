<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\PayrollDeduction;

class PayrollDeductionSeeder extends Seeder
{
    public function run()
    {
        $deductions = [
            [
                'name' => 'Absent and leave deduction',
                'description' => 'Deduction for absences and leaves',
                'is_enabled' => true,
            ],
            [
                'name' => 'Late coming deduction',
                'description' => 'Deduction for late arrivals',
                'is_enabled' => true,
            ],
            [
                'name' => 'Part time employees deduction',
                'description' => 'Deduction for part time employees',
                'is_enabled' => true,
            ],
        ];
        foreach ($deductions as $deduction) {
            PayrollDeduction::updateOrCreate(['name' => $deduction['name']], $deduction);
        }
    }
}

