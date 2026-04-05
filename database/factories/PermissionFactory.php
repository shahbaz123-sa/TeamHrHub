<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Permission;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'guard_name' => $this->faker->randomElement(['web', 'api']),
        ];
    }

    public function withSpecificName(string $name)
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
            ];
        });
    }
}
