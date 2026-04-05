<?php

namespace Modules\HRM\Database\Factories;

use Modules\HRM\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            //'code' => strtoupper($this->faker->unique()->lexify('D??')),
            'status' => $this->faker->boolean,
            'description' => $this->faker->sentence
        ];
    }
}
