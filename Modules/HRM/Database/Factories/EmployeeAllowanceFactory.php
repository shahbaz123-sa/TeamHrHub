<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\EmployeeAllowance;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Allowance;

class EmployeeAllowanceFactory extends Factory
{
    protected $model = EmployeeAllowance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'allowance_id' => Allowance::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
