<?php

namespace Modules\HRM\Database\Factories;

use Modules\HRM\Models\Employee;
use Modules\HRM\Models\EmployeeSalary;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeSalaryFactory extends Factory
{
    protected $model = EmployeeSalary::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'payslip_type_id' => 1,
            'amount' => $this->faker->numberBetween(30000, 200000),
            'effective_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => $this->faker->boolean(80),
            'is_tax_applicable' => false,
            'tax_slab_id' => null,
            'tax_amount' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
