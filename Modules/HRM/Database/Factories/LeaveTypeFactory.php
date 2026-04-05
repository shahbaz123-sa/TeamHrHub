<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\LeaveType;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'quota' => $this->faker->numberBetween(5, 30),
        ];
    }
}
