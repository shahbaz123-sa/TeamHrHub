<?php

namespace Modules\HRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $checkIn = $this->faker->time('H:i:s');
        $checkOut = $this->faker->time('H:i:s', $checkIn);
        $status = $this->faker->randomElement(['present', 'late', 'half_day']);

        return [
            'employee_id' => Employee::factory(),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'latitude_in' => $this->faker->latitude,
            'longitude_in' => $this->faker->longitude,
            'latitude_out' => $this->faker->latitude,
            'longitude_out' => $this->faker->longitude,
            'status' => $status,
            'late_minutes' => $status === 'late' ? $this->faker->numberBetween(1, 120) : 0,
            'early_leaving_minutes' => $this->faker->boolean(20) ? $this->faker->numberBetween(1, 60) : 0,
            'overtime_minutes' => $this->faker->boolean(30) ? $this->faker->numberBetween(1, 180) : 0,
            'note' => $this->faker->boolean(30) ? $this->faker->sentence : null,
            'created_by' => 1, // Default admin user
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
        ];
    }

    public function present(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'present',
                'late_minutes' => 0,
            ];
        });
    }

    public function late(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'late',
                'late_minutes' => $this->faker->numberBetween(1, 120),
            ];
        });
    }

    public function absent(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'check_in' => null,
                'check_out' => null,
                'status' => 'absent',
                'late_minutes' => 0,
                'early_leaving_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        });
    }

    public function halfDay(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'half_day',
                'check_out' => $this->faker->time('H:i:s', '12:00:00'),
            ];
        });
    }

    public function withEmployee(Employee $employee): Factory
    {
        return $this->state(function (array $attributes) use ($employee) {
            return [
                'employee_id' => $employee->id,
            ];
        });
    }

    public function forDate(string $date): Factory
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'date' => $date,
            ];
        });
    }
}
