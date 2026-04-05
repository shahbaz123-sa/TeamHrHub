<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\EmployeeDeduction;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Deduction;

class EmployeeDeductionFactory extends Factory
{
    protected $model = EmployeeDeduction::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'deduction_id' => Deduction::factory(),
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement([1, 2, 3]),
            'amount' => $this->faker->randomFloat(2, 100, 2000),
        ];
    }
}
