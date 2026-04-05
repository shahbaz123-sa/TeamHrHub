<?php

namespace Modules\HRM\Http\Controllers\CEO;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\HRM\Models\Asset;
use Modules\HRM\Models\AssetType;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Department;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Ticket;
use Modules\HRM\Repositories\AttendanceRepository;
use Modules\HRM\Repositories\LeaveRepository;

class DashboardController extends Controller
{
    public function weeklyStats()
    {
        try{
            // Attendance
            $today = now()->format('Y-m-d');
            $weekBefore = now()->subDays(6)->format('Y-m-d');
            $firstDayOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
            $attendanceRepository = app(AttendanceRepository::class);
            $todayAttStats = $attendanceRepository->getAttendanceStats([
                'start_date' => $today
            ]);
            $todayAttStats['total_checkout'] = Attendance::where('date', $today)->whereNotNull('check_out')->whereIn('status', ['present', 'late'])->count();
            $thisWeekDashboardStats = $attendanceRepository->getAttendanceStats([
                'start_date' => $weekBefore,
                'end_date' => $today,
            ]);
            $thisMonthAttStats = $attendanceRepository->getAttendanceStats([
                'start_date' => $firstDayOfMonth,
                'end_date' => $today,
            ]);
            unset($todayAttStats['notMarked_employees']);
            unset($thisWeekDashboardStats['absent_employees']);
            unset($thisMonthAttStats['absent_employees']);
            $thisWeekHiring = Employee::where('employment_status_id', 1)->where('date_of_joining', '>=', $weekBefore)->count();

            $days = [];
            $query = Attendance::query();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $days[] = substr(now()->subDays($i)->format('D'), 0, 2);
                $counts[] = $query->clone()->where('date', $date)->where('status', 'present')->count();
            }

            //Tickets
            $ticketQuery = Ticket::query();
            $totalTickets = $ticketQuery->clone()->count();
            $newTickets = $ticketQuery->clone()->whereIn('status', ['Open'])->count();
            $incompleteTickets = $ticketQuery->clone()->whereIn('status', ['Pending'])->count();
            $completedTickets = $ticketQuery->clone()->whereIn('status', ['Resolved', 'Closed'])->count();

            $tickets = [
                'total_ticket' => $totalTickets,
                'new_ticket' => $newTickets,
                'incomplete_ticket' => $incompleteTickets,
                'completed_ticket' => $completedTickets
            ];

            //Departments
            $departments = Department::whereHas('employees', function ($query) {
                $query->where('employment_status_id', 1);
            })
                ->withCount(['employees' => function ($query) {
                    $query->where('employment_status_id', 1); // only active employees
                }])
                ->orderByDesc('employees_count')
                ->get();

            $departments = $departments->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'employees' => $d->employees_count,
                ];
            });

            // Assets
            $allAssets = AssetType::withCount('assets')
                ->whereHas('assets') // ensures only AssetTypes with at least 1 asset
                ->orderByDesc('assets_count')
                ->get();

            $assetsQuery = Asset::query();
            $assets = [
                'all_assets' => $allAssets,
                'total_assets' => $assetsQuery->clone()->count(),
                'assigned_assets' => $assetsQuery->clone()->whereNotNull('assign_to')->count(),
                'unassigned_assets' => $assetsQuery->clone()->whereNull('assign_to')->count(),
                ];

            $response = [
                'todayAttStats' => $todayAttStats,
                'employeeCounts' => $thisWeekDashboardStats,
                'monthlyAttStats' => $thisMonthAttStats,
                'increaseInHiring' => round(($thisWeekHiring / ($thisWeekDashboardStats['total_employees'] - $thisWeekHiring)) * 100, 1),
                'weekDays' => $days,
                'dayWiseCounts' => $counts,
                'tickets' => $tickets,
                'departments' => $departments,
                'assets' => $assets,
            ];

            return $response;
        }
        catch (\Exception $exception){
            //dd($exception);
        }
    }
}
