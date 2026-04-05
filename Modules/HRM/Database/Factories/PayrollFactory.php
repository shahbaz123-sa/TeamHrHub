<?php

namespace Modules\HRM\Database\Factories;

use Modules\HRM\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\Employee;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\HRM\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payroll::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basicSalary = $this->faker->randomFloat(2, 30000, 150000);
        $totalAllowances = $this->faker->randomFloat(2, 0, 20000);
        $totalDeductions = $this->faker->randomFloat(2, 0, 15000);
        $totalLoans = $this->faker->randomFloat(2, 0, 10000);
        
        return [
            'employee_id' => Employee::factory(),
            'month' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m'),
            'basic_salary' => $basicSalary,
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'total_loans' => $totalLoans,
            'net_salary' => $basicSalary + $totalAllowances - $totalDeductions - $totalLoans,
        ];
    }

    /**
     * Indicate that the payroll is for a specific month.
     */
    public function forMonth(string $month): static
    {
        return $this->state(fn (array $attributes) => [
            'month' => $month,
        ]);
    }

    /**
     * Indicate that the payroll is for a specific employee.
     */
    public function forEmployee(int $employeeId): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employeeId,
        ]);
    }
}
