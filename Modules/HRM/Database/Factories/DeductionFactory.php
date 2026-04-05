<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\Deduction;

class DeductionFactory extends Factory
{
    protected $model = Deduction::class;
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
        ];
    }
}
