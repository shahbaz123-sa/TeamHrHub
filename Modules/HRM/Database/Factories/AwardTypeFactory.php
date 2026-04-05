<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\AwardType;

class AwardTypeFactory extends Factory
{
    protected $model = AwardType::class;
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
        ];
    }
}
