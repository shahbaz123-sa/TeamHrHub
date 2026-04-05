<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;
use Modules\HRM\Models\Leave;
use Modules\HRM\Models\Holiday;

class UpdateAttendanceStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $now = now();
        $today = $now->toDateString();
        $dayName = $now->format('l');

        // update leave status
        $leaveAbleStatuses = ['shift-awaiting', 'absent', 'not-marked'];

        $base = Leave::query()
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('manager_status', '!=', 'rejected')
            ->where('hr_status', '!=', 'rejected');

        // Full leave
        $fullLeaveEmployeeIds = (clone $base)
            ->where('days', '>=', 1)
            ->pluck('employee_id');

        Attendance::where('date', $today)
            ->whereIn('status', $leaveAbleStatuses)
            ->whereIn('employee_id', $fullLeaveEmployeeIds)
            ->update([
                'status' => 'leave',
                'updated_at' => $now,
            ]);

        // Half leave (exactly 0.5)
        $halfLeaveEmployeeIds = (clone $base)
            ->where('days', 0.5)
            ->pluck('employee_id');

        Attendance::where('date', $today)
            ->whereIn('status', $leaveAbleStatuses)
            ->whereIn('employee_id', $halfLeaveEmployeeIds)
            ->update([
                'status' => 'half-leave',
                'updated_at' => $now,
            ]);

        // Short leave (< 0.5)
        $shortLeaveEmployeeIds = (clone $base)
            ->where('days', '<', 0.5)
            ->pluck('employee_id');

        Attendance::where('date', $today)
            ->whereIn('status', $leaveAbleStatuses)
            ->whereIn('employee_id', $shortLeaveEmployeeIds)
            ->update([
                'status' => 'short-leave',
                'updated_at' => $now,
            ]);
        // update holiday status (simplified)
        $month = $now->format('m');
        $day = $now->format('d');

        $isHoliday = Holiday::whereDate('date', $today)
            ->orWhere(function ($q) use ($month, $day) {
                $q->where('is_recurring', true)
                  ->whereMonth('date', $month)
                  ->whereDay('date', $day);
            })
            ->exists();

        if ($isHoliday) {
            Attendance::where('date', $today)
                ->whereIn('status', $leaveAbleStatuses)
                ->update([
                    'status' => 'holiday',
                    'updated_at' => $now,
                ]);
        } else {
            $holidayEmployees = EmployeeAttendanceDay::where('day', $dayName)
                ->where('is_working_day', false)
                ->pluck('employee_id');

            foreach ($holidayEmployees as $holidayEmployee) {
                Attendance::where('employee_id', $holidayEmployee)
                    ->where('date', $today)
                    ->whereIn('status', $leaveAbleStatuses)
                    ->update([
                        'status' => 'holiday',
                        'updated_at' => $now,
                    ]);
            }
        }
        // update not-marked
        $attendances = Attendance::with('employee.attendanceDays')
            ->where('date', $today)
            ->where('status', 'shift-awaiting')
            ->get();

        foreach ($attendances as $attendance) {
            $employee = $attendance->employee;
            // Skip if employee is missing
            if (!$employee) {
                continue;
            }
            $attendanceDay = $employee->attendanceDays
                ->where('day', $dayName)
                ->where('is_working_day', true)
                ->first();

            if ($attendanceDay && $attendanceDay->checkin_time) {
                $scheduledCheckIn = Carbon::parse($today . ' ' . $attendanceDay->checkin_time);
                if ($now->greaterThan($scheduledCheckIn)) {
                    $attendance->update([
                        'status' => 'not-marked',
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        //update absent
        $attendances = Attendance::with(['employee.attendanceDays'])
            ->where('date', $today)
            ->whereIn('status', ['shift-awaiting', 'not-marked'])
            ->whereHas('employee.attendanceDays', function ($q) use ($dayName) {
                $q->where('day', $dayName)
                    ->where('is_working_day', true);
            })
            ->get();

        foreach ($attendances as $attendance) {
            $employee = $attendance->employee;
            $attendanceDay = $employee->attendanceDays
                ->where('day', $dayName)
                ->where('is_working_day', true)
                ->first();

            if ($attendanceDay && $attendanceDay->checkout_time) {
                $scheduledCheckout = Carbon::parse($today . ' ' . $attendanceDay->checkout_time);
                if ($now->greaterThan($scheduledCheckout)) {
                    $attendance->update([
                        'status' => 'absent',
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
