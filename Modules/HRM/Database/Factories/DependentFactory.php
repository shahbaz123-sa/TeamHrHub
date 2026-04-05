<?php

namespace Modules\HRM\Database\Factories;

use Modules\HRM\Models\Dependent;
use Illuminate\Database\Eloquent\Factories\Factory;

class DependentFactory extends Factory
{
    protected $model = Dependent::class;

    public function definition()
    {
        return [
            'employee_id' => \Modules\HRM\Models\Employee::factory(),
            'name' => $this->faker->name,
            'relation' => $this->faker->randomElement(['Spouse', 'Child', 'Parent', 'Sibling']),
            'phone' => $this->faker->phoneNumber,
            'date_of_birth' => $this->faker->date(),
            'age' => $this->faker->numberBetween(1, 80)
        ];
    }
}
