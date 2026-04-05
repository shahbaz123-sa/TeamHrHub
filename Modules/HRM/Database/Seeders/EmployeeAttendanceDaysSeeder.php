<?php

namespace Modules\HRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;

class EmployeeAttendanceDaysSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {

            // Days list EXACTLY like your table
            $days = [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ];

            foreach ($days as $day) {

                // Find the row for this employee + day
                $attendance = EmployeeAttendanceDay::where('employee_id', $employee->id)
                    ->where('day', $day)
                    ->first();

                if (!$attendance) {
                    continue;
                }

                // If NOT a working day → clear times
                if (!$attendance->is_working_day) {
                    $attendance->update([
                        'checkin_time' => null,
                        'checkout_time' => null,
                    ]);
                    continue;
                }


                // Working day → assign default checkin/checkout
                $attendance->update([
                    'checkin_time'  => '09:00:00',
                    'checkout_time' => '18:00:00',
                ]);
            }
        }

        echo "Employee attendance days updated.\n";
    }
}
