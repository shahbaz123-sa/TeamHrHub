<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee;
use Carbon\Carbon;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;
use Modules\HRM\Models\Branch;

class AddAttendanceRecords implements ShouldQueue
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
    public function handle(): void
    {
        $today = Carbon::now();
        $todayDate = $today->toDateString();
        $dayName = $today->format('l');

        $employees = Employee::where('id', '!=', 1)
            ->where('employment_status_id', 1)
            ->with([
                'branch',
                'attendanceDays' => function ($query) use ($dayName) {
                    $query->where('day', $dayName);
                },
            ])
            ->whereDoesntHave('attendances', function ($q) use ($todayDate) {
                $q->whereDate('date', $todayDate);
            })
            ->get();

        foreach ($employees as $employee) {
            [$scheduledCheckIn, $scheduledCheckOut] = $this->resolveScheduledTimes($employee, $todayDate, $dayName);

            Attendance::create(
                [
                    'employee_id' => $employee->id,
                    'date'        => $todayDate,
                    'status' => 'shift-awaiting',
                    'late_minutes' => 0,
                    'early_leaving_minutes' => 0,
                    'overtime_minutes' => 0,
                    'early_check_in_minutes' => 0,
                    'check_in_from' => 0,
                    'check_out_from' => 0,
                    'created_by' => null,
                    'check_in_time' => $scheduledCheckIn,
                    'check_out_time' => $scheduledCheckOut,
                ]
            );

        }
    }

    private function resolveScheduledTimes(Employee $employee, string $date, string $dayName): array
    {
        $attendanceDay = $employee->relationLoaded('attendanceDays')
            ? $employee->attendanceDays->first()
            : EmployeeAttendanceDay::where('employee_id', $employee->id)
                ->where('day', $dayName)
                ->first();

        if ($attendanceDay && !$attendanceDay->is_working_day) {
            $attendanceDay = null;
        }

        $branch = $employee->branch;

        $baseCheckIn = $attendanceDay?->checkin_time ?? $branch?->office_start_time;
        $baseCheckOut = $attendanceDay?->checkout_time ?? $branch?->office_end_time;

        $deviation = $this->getDeviationForDay($branch, $dayName);

        $checkInDeviation = $deviation['check_in_deviation'] ?? 0;
        $checkOutDeviation = $deviation['check_out_deviation'] ?? 0;

        return [
            $this->applyDeviation($date, $baseCheckIn, $checkInDeviation),
            $this->applyDeviation($date, $baseCheckOut, $checkOutDeviation),
        ];
    }

    private function applyDeviation(string $date, ?string $time, $deviation): ?string
    {
        if (!$time) {
            return null;
        }

        return Carbon::parse($date . ' ' . $time)
            ->addMinutes((int) $deviation)
            ->format('H:i:s');
    }

    private function getDeviationForDay(?Branch $branch, string $dayName): ?array
    {
        if (!$branch || empty($branch->time_deviations)) {
            return null;
        }

        foreach ($branch->time_deviations as $deviation) {
            if (($deviation['day'] ?? null) === $dayName) {
                return $deviation;
            }
        }

        return null;
    }
}
