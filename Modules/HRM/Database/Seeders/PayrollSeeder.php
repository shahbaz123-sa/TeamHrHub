<?php

namespace Modules\HRM\Database\Seeders;

use Modules\HRM\Models\Payroll;
use Illuminate\Database\Seeder;
use Modules\HRM\Models\Employee;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some employees to create payrolls for
        $employees = Employee::take(5)->get();

        if ($employees->isEmpty()) {
            $this->command->info('No employees found. Please run EmployeeSeeder first.');
            return;
        }

        // Create payrolls for the last 3 months
        $months = [
            now()->subMonths(2)->format('Y-m'),
            now()->subMonth()->format('Y-m'),
            now()->format('Y-m'),
        ];

        foreach ($employees as $employee) {
            foreach ($months as $month) {
                // Check if payroll already exists
                $existingPayroll = Payroll::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->first();

                if (!$existingPayroll) {
                    Payroll::factory()
                        ->forEmployee($employee->id)
                        ->forMonth($month)
                        ->create();
                }
            }
        }

        $this->command->info('Payrolls seeded successfully!');
    }
}
