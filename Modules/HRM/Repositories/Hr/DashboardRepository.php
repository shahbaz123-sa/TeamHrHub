<?php

namespace Modules\HRM\Repositories\Hr;

use Carbon\Carbon;
use Modules\HRM\Models\Leave;
use Modules\HRM\Repositories\AttendanceRepository;
use Modules\HRM\Repositories\LeaveRepository;

class DashboardRepository
{
    public function getDashboardData()
    {
        $todayDate = now()->format('Y-m-d');
        $thisMonthStart = now()->startOfMonth()->format('Y-m-d');
        $thisMonthEnd = now()->endOfMonth()->format('Y-m-d');

        $attendanceRepository = app(AttendanceRepository::class);

        $todayDashboardStats = $attendanceRepository->getAttendanceStats(['date' => $todayDate]);
        $thisMonthDashboardStats = $attendanceRepository->getAttendanceStats([
            'start_date' => $thisMonthStart,
            'end_date' => $thisMonthEnd,
        ]);

        $leaveDashboardStats = app(LeaveRepository::class)->getLeaveStats();

        return [
            'today_stats' => $todayDashboardStats + $leaveDashboardStats['today'],
            'this_month_stats' => $thisMonthDashboardStats + $leaveDashboardStats['this_month'],
        ];
    }
}
