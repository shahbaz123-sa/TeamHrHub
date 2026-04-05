<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Branch;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('date');
            $table->time('check_out_time')->nullable()->after('check_in_time');
        });

        Attendance::query()
            ->with(['employee.branch'])
            ->chunkById(100, function ($attendances) {
                foreach ($attendances as $attendance) {
                    $scheduled = $this->resolveScheduledTimes($attendance->employee, $attendance->date);
                    Attendance::whereKey($attendance->id)->update($scheduled);
                }
            });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time']);
        });
    }

    private function resolveScheduledTimes(?Employee $employee, $date): array
    {
        if (!$employee) {
            return ['check_in_time' => null, 'check_out_time' => null];
        }

        try {
            $dayName = Carbon::parse($date)->format('l');
        } catch (\Throwable $e) {
            return ['check_in_time' => null, 'check_out_time' => null];
        }

        $attendanceDay = EmployeeAttendanceDay::where('employee_id', $employee->id)
            ->where('day', $dayName)
            ->where('is_working_day', true)
            ->first();

        $branch = $employee->branch;

        $baseCheckIn = $attendanceDay?->checkin_time ?? $branch?->office_start_time;
        $baseCheckOut = $attendanceDay?->checkout_time ?? $branch?->office_end_time;

        $deviation = $this->getDeviationForDay($branch, $dayName);

        $checkInDeviation = $deviation['check_in_deviation'] ?? 0;
        $checkOutDeviation = $deviation['check_out_deviation'] ?? 0;

        return [
            'check_in_time' => $this->applyDeviation($date, $baseCheckIn, $checkInDeviation),
            'check_out_time' => $this->applyDeviation($date, $baseCheckOut, $checkOutDeviation),
        ];
    }

    private function applyDeviation($date, ?string $time, $deviation): ?string
    {
        if (!$time) {
            return null;
        }

        try {
            // Normalize to date-only part to prevent double time strings
            $day = Carbon::parse($date)->toDateString();
            $base = Carbon::parse(trim($day . ' ' . trim($time)));
        } catch (\Throwable $e) {
            return null;
        }

        return $base
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
};

