<?php

namespace Modules\HRM\Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\HRM\Models\Asset;
use Modules\HRM\Models\Branch;
use Illuminate\Database\Seeder;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Dependent;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Department;
use Modules\HRM\Models\Designation;
use Modules\HRM\Models\JobCategory;
use Illuminate\Support\Facades\Hash;
use Modules\HRM\Models\EmployeeAsset;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::truncate();
        Employee::factory()->count(5)->create()->each(function ($employee) {
            $user = User::firstOrCreate([
                'email' => $employee->official_email ?? Str::slug($employee->name) . '@example.com'
            ], [
                'name' => $employee->name,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $user->assignRole('Employee');

            $employee->update([
                'user_id' => $user->id,
                'branch_id' => Branch::inRandomOrder()->first()->id,
                'department_id' => Department::inRandomOrder()->first()->id,
                'designation_id' => Designation::inRandomOrder()->first()->id,
                'job_category_id' => JobCategory::inRandomOrder()->first()->id,
            ]);

            $this->generateAttendanceRecords($employee);
        });
    }

    protected function generateAttendanceRecords($employee)
    {
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays(30 - $i)->format('Y-m-d');

            if (rand(1, 5) > 1 || in_array(date('N', strtotime($date)), [6, 7])) {
                continue;
            }

            if (rand(1, 10) > 1) {
                $checkIn = fake()->dateTimeBetween('08:00', '10:30')->format('H:i');
                $checkOut = fake()->dateTimeBetween('16:00', '19:00')->format('H:i');
                Attendance::factory()->create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'status' => $checkIn > '09:30' ? 'late' : 'present',
                    'late_minutes' => $checkIn > '09:30' ? (strtotime($checkIn) - strtotime('09:30')) / 60 : 0,
                    'early_leaving_minutes' => $checkOut < '17:00' ? (strtotime('17:00') - strtotime($checkOut)) / 60 : 0,
                    'overtime_minutes' => $checkOut > '17:00' ? (strtotime($checkOut) - strtotime('17:00')) / 60 : 0
                ]);
            } else {
                Attendance::factory()->absent()->create([
                    'employee_id' => $employee->id,
                    'date' => $date
                ]);
            }
        }
    }
}
