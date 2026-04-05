<?php

namespace Modules\HRM\Database\Factories;

use Modules\HRM\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Laptop', 'Charger', 'Monitor', 'Mouse', 'Keyboard']),
            'serial_no' => $this->faker->uuid(),
            'purchase_date' => optional($this->faker->optional()->dateTimeBetween('now', '+1 year'))->format('Y-m-d'),
            'support_until' => optional($this->faker->optional()->dateTimeBetween('now', '+1 year'))->format('Y-m-d'),
        ];
    }
}
