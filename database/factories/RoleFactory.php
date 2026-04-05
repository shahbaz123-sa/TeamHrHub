<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'guard_name' => $this->faker->randomElement(['web', 'api']),
            'team_foreign_key' => null, // Adjust if using teams
        ];
    }

    public function withPermissions(array $permissions = [])
    {
        return $this->afterCreating(function (Role $role) use ($permissions) {
            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions(
                    \Spatie\Permission\Models\Permission::factory()
                        ->count(3)
                        ->create()
                );
            }
        });
    }

    public function forTeam($teamId)
    {
        return $this->state(function (array $attributes) use ($teamId) {
            return [
                'team_foreign_key' => $teamId,
            ];
        });
    }
}
