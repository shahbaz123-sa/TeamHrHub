<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\EmployeeLoan;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\LoanOption;

class EmployeeLoanFactory extends Factory
{
    protected $model = EmployeeLoan::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'loan_id' => LoanOption::factory(),
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement([1, 2, 3]),
            'reasons' => $this->faker->paragraph(3),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'status' => $this->faker->randomElement([1, 2, 3]),
        ];
    }
}
