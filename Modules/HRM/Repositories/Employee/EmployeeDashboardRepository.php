<?php

namespace Modules\HRM\Repositories\Employee;

use Carbon\Carbon;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Leave;
use Modules\HRM\Models\Holiday;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;
use Modules\HRM\Repositories\AttendanceRepository;
use Modules\HRM\Repositories\LeaveRepository;

class EmployeeDashboardRepository
{
    /**
     * Get employee dashboard data including attendance statistics
     *
     * @param int $employeeId
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getEmployeeDashboardData(int $employeeId, int $month, int $year): array
    {
        $todayName = now()->format('l');
        $today = Carbon::today()->toDateString();
        $canCheckIn = false;

        $markedForToday = Attendance::where('date', $today)->where('employee_id', $employeeId)->where('check_in', '!=', null)->exists();
        if(!$markedForToday){
            $lateAllowed = EmployeeAttendanceDay::where('employee_id', $employeeId)
                ->where('day', $todayName)
                ->where('allow_late_checkin', true)
                ->exists();
            $timeOver = now()->format('H:i:s') > '12:00:00';
            $canCheckIn = $lateAllowed || !$timeOver;
        }

        return [
            'attendance_stats' => $this->getAttendanceStatistics($employeeId, $month, $year),
            'can_checkin' => $canCheckIn,
            'period_info' => [
                'month' => $month,
                'year' => $year,
                'month_name' => Carbon::create($year, $month)->format('F'),
                'total_days' => Carbon::create($year, $month)->daysInMonth,
            ]
        ];
    }

    /**
     * Get attendance statistics for the specified period
     *
     * @param int $employeeId
     * @param int $month
     * @param int $year
     * @return array
     */
    protected function getAttendanceStatistics(int $employeeId, int $month, int $year): array
    {
        $attendanceRecords = $this->getAttendanceRecords($employeeId, $month, $year);

        return [
            'total_early_check_in' => $this->calculateEarlyCheckInCount($attendanceRecords),
            'total_late_check_in' => $this->calculateLateCheckInCount($attendanceRecords),
            'total_early_check_out' => $this->calculateEarlyCheckOutCount($attendanceRecords),
            'total_late_check_out' => $this->calculateLateCheckOutCount($attendanceRecords),
            'total_presents' => $this->calculateTotalPresents($attendanceRecords),
            'total_leaves' => $this->calculateTotalLeaves($employeeId, $month, $year),
            'total_absent' => $this->calculateTotalAbsent($employeeId, $month, $year),
            'leave_breakdown' => $this->getLeaveBreakdown($employeeId, $month, $year),
            'total_records' => $attendanceRecords->count(),
        ];
    }

    /**
     * Get attendance records for the specified period
     *
     * @param int $employeeId
     * @param int $month
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAttendanceRecords(int $employeeId, int $month, int $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        
        // Determine end date based on current month vs selected month
        $currentDate = Carbon::now();
        $isCurrentMonth = ($year == $currentDate->year && $month == $currentDate->month);
        
        if ($isCurrentMonth) {
            // For current month: calculate from 1st to today
            $endDate = $currentDate->copy()->endOfDay();
        } else {
            // For previous months: calculate full month
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        return Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
    }

    /**
     * Calculate total early check-in count
     * Early check-in is when early_check_in_minutes > 0
     *
     * @param \Illuminate\Database\Eloquent\Collection $attendanceRecords
     * @return int
     */
    protected function calculateEarlyCheckInCount($attendanceRecords): int
    {
        return $attendanceRecords->where('early_check_in_minutes', '>', 0)->count();
    }

    /**
     * Calculate total late check-in count
     * Late check-in is when late_minutes > 0
     *
     * @param \Illuminate\Database\Eloquent\Collection $attendanceRecords
     * @return int
     */
    protected function calculateLateCheckInCount($attendanceRecords): int
    {
        return $attendanceRecords->where('status', 'late')->count();
    }

    /**
     * Calculate total early check-out count
     * Early check-out is when early_leaving_minutes > 0
     *
     * @param \Illuminate\Database\Eloquent\Collection $attendanceRecords
     * @return int
     */
    protected function calculateEarlyCheckOutCount($attendanceRecords): int
    {
        return $attendanceRecords->where('early_leaving_minutes', '>', 0)->count();
    }

    /**
     * Calculate total late check-out count (overtime)
     * Late check-out is when overtime_minutes > 0
     *
     * @param \Illuminate\Database\Eloquent\Collection $attendanceRecords
     * @return int
     */
    protected function calculateLateCheckOutCount($attendanceRecords): int
    {
        return $attendanceRecords->where('overtime_minutes', '>', 0)->count();
    }

    /**
     * Calculate total presents count
     * Present is when check_in is not null
     *
     * @param \Illuminate\Database\Eloquent\Collection $attendanceRecords
     * @return int
     */
    protected function calculateTotalPresents($attendanceRecords): int
    {
        return $attendanceRecords->whereNotNull('check_in')->count();
    }

    /**
     * Calculate total leaves count
     * Sum the 'days' field from approved leaves
     *
     * @param int $employeeId
     * @param int $month
     * @param int $year
     * @return float
     */
    protected function calculateTotalLeaves(int $employeeId, int $month, int $year): float
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        
        // Determine end date based on current month vs selected month
        $currentDate = Carbon::now();
        $isCurrentMonth = ($year == $currentDate->year && $month == $currentDate->month);
        
        if ($isCurrentMonth) {
            // For current month: calculate from 1st to today
            $endDate = $currentDate->copy()->endOfDay();
        } else {
            // For previous months: calculate full month
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        return Leave::where('employee_id', $employeeId)
            ->where('hr_status', 'approved')
            ->where('manager_status', 'approved')
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('days');
    }


    /**
     * Calculate total absent days
     * Absent = Days with no check-in AND no approved FULL DAY leave
     * Half day and short leaves are not excluded from absent calculation
     *
     * @param int $employeeId
     * @param int $month
     * @param int $year
     * @return int
     */
    protected function calculateTotalAbsent(int $employeeId, int $month, int $year): int
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        
        // Determine end date based on current month vs selected month
        $currentDate = Carbon::now();
        $isCurrentMonth = ($year == $currentDate->year && $month == $currentDate->month);
        $isFutureMonth = ($year > $currentDate->year) || 
                        ($year == $currentDate->year && $month > $currentDate->month);
        
        // For future months, return 0 as no attendance data exists yet
        // This prevents showing high absent counts for months that haven't occurred
        if ($isFutureMonth) {
            return 0;
        }
        
        if ($isCurrentMonth) {
            // For current month: calculate from 1st to today
            $endDate = $currentDate->copy()->endOfDay();
        } else {
            // For previous months: calculate full month
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }
        
        // Get employee's working days pattern
        $workingDays = EmployeeAttendanceDay::where('employee_id', $employeeId)
            ->where('is_working_day', true)
            ->pluck('day')
            ->toArray();
        
        // Get days with check-in (present days)
        $presentDays = Attendance::where('employee_id', $employeeId)
            ->whereNotNull('check_in')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Get days with approved FULL DAY leaves only
        $leaveDays = Leave::where('employee_id', $employeeId)
            ->where('hr_status', 'approved')
            ->where('manager_status', 'approved')
            ->where('duration_type', Leave::DURATION_FULL_DAY)  // ✅ Only full day leaves
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->flatMap(function ($leave) {
                $dates = [];
                $currentDate = Carbon::parse($leave->start_date);
                $endDate = Carbon::parse($leave->end_date);
                
                // Generate all dates in the leave period
                while ($currentDate->lte($endDate)) {
                    $dates[] = $currentDate->format('Y-m-d');
                    $currentDate->addDay();
                }
                
                return $dates;
            })
            ->unique()
            ->toArray();
        
        // Get holiday days for the period
        $holidayDays = $this->getHolidayDays($startDate, $endDate, $year);
        
        // Calculate absent days
        $absentDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $dayName = $currentDate->format('l'); // Monday, Tuesday, etc.
            
            // Check if it's a working day for this employee
            $isWorkingDay = in_array($dayName, $workingDays);
            
            // Only calculate absent days for working days
            if ($isWorkingDay) {
                // Check if employee was present (has check-in)
                $wasPresent = in_array($dateString, $presentDays);
                
                // Check if employee was on approved FULL DAY leave
                $wasOnFullDayLeave = in_array($dateString, $leaveDays);
                
                // Check if it's a holiday
                $isHoliday = in_array($dateString, $holidayDays);
                
                // If neither present, nor on full day leave, nor holiday, count as absent
                if (!$wasPresent && !$wasOnFullDayLeave && !$isHoliday) {
                    $absentDays++;
                }
            }
            
            $currentDate->addDay();
        }
        
        return $absentDays;
    }

    /**
     * Get holiday days for the specified period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $year
     * @return array
     */
    protected function getHolidayDays(Carbon $startDate, Carbon $endDate, int $year): array
    {
        $holidayDays = [];
        
        // Get recurring holidays (apply to all years)
        $recurringHolidays = Holiday::where('is_recurring', true)
            ->get()
            ->map(function ($holiday) use ($year) {
                // For recurring holidays, use the same month and day but with the target year
                $holidayDate = Carbon::parse($holiday->date);
                return $holidayDate->setYear($year)->format('Y-m-d');
            })
            ->toArray();
        
        // Get non-recurring holidays for the specific year
        $nonRecurringHolidays = Holiday::where('is_recurring', false)
            ->whereYear('date', $year)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Combine all holiday days
        $holidayDays = array_merge($recurringHolidays, $nonRecurringHolidays);
        
        // Filter holidays that fall within the date range
        $holidayDays = array_filter($holidayDays, function ($holidayDate) use ($startDate, $endDate) {
            $date = Carbon::parse($holidayDate);
            return $date->between($startDate, $endDate);
        });
        
        return array_values($holidayDays);
    }

    /**
     * Get leave breakdown by duration type
     *
     * @param int $employeeId
     * @param int $month
     * @param int $year
     * @return array
     */
    protected function getLeaveBreakdown(int $employeeId, int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        
        // Determine end date based on current month vs selected month
        $currentDate = Carbon::now();
        $isCurrentMonth = ($year == $currentDate->year && $month == $currentDate->month);
        
        if ($isCurrentMonth) {
            // For current month: calculate from 1st to today
            $endDate = $currentDate->copy()->endOfDay();
        } else {
            // For previous months: calculate full month
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        $leaves = Leave::where('employee_id', $employeeId)
            ->where('hr_status', 'approved')
            ->where('manager_status', 'approved')
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $breakdown = [
            'full_day_leaves' => 0,
            'half_day_leaves' => 0,
            'short_leaves' => 0,
        ];

        foreach ($leaves as $leave) {
            switch ($leave->duration_type) {
                case Leave::DURATION_FULL_DAY:
                    $breakdown['full_day_leaves'] += $leave->days;
                    break;
                
                case Leave::DURATION_HALF_DAY:
                    $breakdown['half_day_leaves'] += $leave->days;
                    break;
                
                case Leave::DURATION_SHORT_LEAVE:
                    $breakdown['short_leaves'] += $leave->days;
                    break;
            }
        }

        return $breakdown;
    }
}
