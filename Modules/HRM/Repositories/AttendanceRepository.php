<?php

namespace Modules\HRM\Repositories;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\HRM\Http\Resources\AttendanceResource;
use Modules\HRM\Models\Employee;
use Illuminate\Support\Facades\DB;
use Modules\HRM\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;
use Modules\HRM\Models\Leave;
use Modules\HRM\Models\Holiday;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function __construct()
    {
        Carbon::setLocale('en');
        date_default_timezone_set('Asia/Karachi');
    }

    public function all(): Collection
    {
        return Attendance::all();
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Attendance::paginate($perPage);
    }

    public function find(int $id): ?Attendance
    {
        return Attendance::find($id);
    }

    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Attendance::find($id)->update($data);
    }

    public function updateAttendance(int $id, array $data): ?Attendance
    {
        $attendance = Attendance::find($id);
        
        if (!$attendance) {
            return null;
        }

        // Normalize status to lowercase for database storage
        if (isset($data['status'])) {
            $data['status'] = strtolower($data['status']);
        }
        
        // Normalize time formats to include seconds if not present
        if (isset($data['check_in']) && $data['check_in']) {
            // If time is in HH:MM format, add seconds
            if (preg_match('/^\d{1,2}:\d{2}$/', $data['check_in'])) {
                $data['check_in'] = $data['check_in'] . ':00';
            }
        }
        
        if (isset($data['check_out']) && $data['check_out']) {
            // If time is in HH:MM format, add seconds
            if (preg_match('/^\d{1,2}:\d{2}$/', $data['check_out'])) {
                $data['check_out'] = $data['check_out'] . ':00';
            }
        }

        // Recalculate attendance metrics if check-in or check-out times are updated
        if (isset($data['check_in']) || isset($data['check_out'])) {
            $data = $this->recalculateAttendanceMetrics($attendance, $data);
        }

        $updated = $attendance->update($data);
        
        return $updated ? $attendance->fresh() : null;
    }

    protected function recalculateAttendanceMetrics(Attendance $attendance, array $data): array
    {
        $employee = $attendance->employee;
        $branch = $employee->branch;

        if (!$branch) {
            return $data; // Cannot calculate without branch information
        }

        $attendanceDate = Carbon::parse($attendance->date);
        $dayOfWeek = $attendanceDate->format('l');

        // Get the scheduled check-in and check-out times for the specific day of the week
        $attendanceDay = EmployeeAttendanceDay::where('employee_id', $employee->id)
            ->where('day', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        // Fallback to branch office times if no specific schedule for that day
        $scheduledCheckIn = $attendanceDay ? $attendanceDay->checkin_time : $branch->office_start_time;
        $scheduledCheckOut = $attendanceDay ? $attendanceDay->checkout_time : $branch->office_end_time;

        // Get time deviation for the specific day
        $todayDeviation = $this->getTodayTimeDeviation($employee);
        $checkInDeviation = $todayDeviation ? $todayDeviation['check_in_deviation'] : 0;
        $checkOutDeviation = $todayDeviation ? $todayDeviation['check_out_deviation'] : 0;

        // Recalculate check-in related metrics
        if (isset($data['check_in']) && $data['check_in']) {
            $checkInTime = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $data['check_in']);

            // Calculate office start time with deviation
            $officeStart = Carbon::parse($checkInTime->toDateString() . ' ' . $scheduledCheckIn)->addMinutes(intval($checkInDeviation));

            // Calculate late minutes with grace period
            $graceEnd = $officeStart->copy()->addMinutes($branch->grace_period);

            if ($checkInTime->lessThanOrEqualTo($graceEnd)) {
                $data['late_minutes'] = 0;
            } else {
                $data['late_minutes'] = (int) floor($checkInTime->diffInSeconds($graceEnd, true) / 60);
            }

            // Calculate early check-in minutes
            if ($checkInTime->greaterThanOrEqualTo($officeStart)) {
                $data['early_check_in_minutes'] = 0;
            } else {
                $data['early_check_in_minutes'] = (int) floor($officeStart->diffInSeconds($checkInTime, true) / 60);
            }
        }

        // Recalculate check-out related metrics
        if (isset($data['check_out']) && $data['check_out']) {
            $checkOutTime = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $data['check_out']);

            // Calculate office end time with deviation
            $officeEnd = Carbon::parse($checkOutTime->toDateString() . ' ' . $scheduledCheckOut)->addMinutes(intval($checkOutDeviation));

            // Calculate early leaving minutes
            if ($checkOutTime->greaterThan($officeEnd)) {
                $data['early_leaving_minutes'] = 0;
            } else {
                $data['early_leaving_minutes'] = (int) floor($officeEnd->diffInSeconds($checkOutTime, true) / 60);
            }

            // Calculate overtime minutes
            if ($checkOutTime->lessThanOrEqualTo($officeEnd)) {
                $data['overtime_minutes'] = 0;
            } else {
                $data['overtime_minutes'] = (int) floor($checkOutTime->diffInSeconds($officeEnd, true) / 60);
            }
        }

        return $data;
    }

    public function delete(int $id): bool
    {
        return Attendance::destroy($id);
    }

    private function getTodayTimeDeviation($employee)
    {
        $today = Carbon::now()->format('l'); // Get day name, e.g., 'Monday'
        $branch = $employee->branch;

        if ($branch && !empty($branch->time_deviations)) {
            foreach ($branch->time_deviations as $deviation) {
                if (isset($deviation['day']) && $deviation['day'] === $today) {
                    return $deviation;
                }
            }
        }

        return null;
    }

    public function checkIn(int $employeeId, array $data): Attendance
    {
        try {
            $now = Carbon::now('Asia/Karachi');
            $checkInTime = $now->format('H:i:s');
            $lateMinutes = $this->calculateLateMinutes($now);

        $earlyCheckInMinutes = $this->calculateEarlyCheckInMinutes($now);

        $status = $lateMinutes > 0 ? 'late' : 'present';

        $isHoliday = Holiday::where('date', $now->format('Y-m-d'))->exists();

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'date' => $now->format('Y-m-d'),
            ],
            [
                'check_in' => $checkInTime,
                'status' => $status,
                'latitude_in' => $data['latitude'] ?? null,
                'longitude_in' => $data['longitude'] ?? null,
                'check_in_address' => $data['check_in_address'] ?? null,
                'late_minutes' => $lateMinutes,
                'early_check_in_minutes' => $earlyCheckInMinutes,
                'accuracy' => $data['accuracy'] ?? null,
                'device' => $data['device'] ?? null,
                'check_in_from' => isset($data['from_office']) && $data['from_office'] ? config('constants.attendances.location.office') : config('constants.attendances.location.other_location'),
                'check_in_other_location' => isset($data['location']) ? $data['location'] : null,
                'is_holiday_work' => $isHoliday,
            ]
        );

            return $attendance->fresh();
        } catch (\Exception $e) {
            // Repository methods should not return HTTP responses. Rethrow so controller can handle it.
            throw $e;
        }
    }

    protected function calculateLateMinutes(Carbon $checkIn)
    {
        $employee = auth()->user()->employee;
        $today = Carbon::now()->format('l');
        $attendanceDay = EmployeeAttendanceDay::where('employee_id', $employee->id)
            ->where('day', $today)
            ->where('is_working_day', true)
            ->first();
        if(!$attendanceDay){
            return 0;
        }
        $scheduledCheckIn  = $attendanceDay->checkin_time ?? null;

        $branch = $employee->branch;
        $officeStart = Carbon::parse($checkIn->toDateString() . ' ' . $scheduledCheckIn);

        $todayDeviation = $this->getTodayTimeDeviation($employee);
        $checkInDeviation = $todayDeviation ? $todayDeviation['check_in_deviation'] : 0;

        $graceEnd = $officeStart->copy()->addMinutes($branch->grace_period + $checkInDeviation);

        if ($checkIn->lessThanOrEqualTo($graceEnd)) {
            return 0;
        }

        $gracePeriods = (int) floor($checkIn->diffInSeconds($graceEnd, true) / 60);
        return $gracePeriods;
    }

    protected function calculateLateMinutesOld(Carbon $checkIn)
    {
        $employee = auth()->user()->employee;
        $branch = $employee->branch;

        $officeStart = Carbon::parse($checkIn->toDateString() . ' ' . $branch->office_start_time); // same date as check-in

        // Add grace period
        $graceEnd = $officeStart->copy()->addMinutes($branch->grace_period);

        if ($checkIn->lessThanOrEqualTo($graceEnd)) {
            return 0; // Within grace period — not late
        }

        // Minutes late beyond grace
        return (int) floor($checkIn->diffInSeconds($graceEnd, true) / 60);
    }

    protected function calculateEarlyCheckInMinutes(Carbon $checkIn)
    {
        $employee = auth()->user()->employee;
        $today = Carbon::now()->format('l');
        $attendanceDay = EmployeeAttendanceDay::where('employee_id', $employee->id)
            ->where('day', $today)
            ->where('is_working_day', true)
            ->first();
        if(!$attendanceDay){
            return 0;
        }
        $scheduledCheckIn  = $attendanceDay->checkin_time ?? null;

        $todayDeviation = $this->getTodayTimeDeviation($employee);
        $checkInDeviation = $todayDeviation ? $todayDeviation['check_in_deviation'] : 0;

        $officeStart = Carbon::parse($checkIn->toDateString() . ' ' . $scheduledCheckIn)->addMinutes(intval($checkInDeviation));

        if ($checkIn->greaterThanOrEqualTo($officeStart)) {
            return 0; // Not early check-in
        }

        // Minutes early before office start time
        return (int) floor($officeStart->diffInSeconds($checkIn, true) / 60);
    }

    protected function calculateEarlyCheckInMinutesOld(Carbon $checkIn)
    {
        $employee = auth()->user()->employee;
        $branch = $employee->branch;

        $officeStart = Carbon::parse($checkIn->toDateString() . ' ' . $branch->office_start_time);

        if ($checkIn->greaterThanOrEqualTo($officeStart)) {
            return 0; // Not early check-in
        }

        // Minutes early before office start time
        return (int) floor($officeStart->diffInSeconds($checkIn, true) / 60);
    }

    public function checkOut(int $employeeId, array $data): Attendance
    {
        $now = Carbon::now('Asia/Karachi');
        $today = $now->format('Y-m-d');
        $checkOutTime = $now->format('H:i:s');
        $earlyLeavingMinutes = $this->calculateEarlyLeavingMinutes($now);
        $overTimeMinutes = $this->calculateOvertimeMinutes($now);

        $attendance = Attendance::updateOrCreate([
            'employee_id' => $employeeId,
            'date' => $today
        ], [
            'check_out' => $checkOutTime,
            'latitude_out' => $data['latitude'] ?? null,
            'longitude_out' => $data['longitude'] ?? null,
            'check_out_address' => $data['check_out_address'] ?? null,
            'early_leaving_minutes' => $earlyLeavingMinutes,
            'overtime_minutes' => $overTimeMinutes,
            'check_out_from' => isset($data['from_office']) && $data['from_office'] ? config('constants.attendances.location.office') : config('constants.attendances.location.other_location'),
            'check_out_other_location' => isset($data['location']) ? $data['location'] : null,
        ]);

        return $attendance;
    }

    protected function calculateEarlyLeavingMinutes(Carbon $checkOut)
    {
        $employee = auth()->user()->employee;
        $today = Carbon::now()->format('l');
        $attendanceDay = EmployeeAttendanceDay::where('employee_id', $employee->id)
            ->where('day', $today)
            ->where('is_working_day', true)
            ->first();
        if(!$attendanceDay){
            return 0;
        }
        $scheduledCheckOut  = $attendanceDay->checkout_time ?? null;

        $todayDeviation = $this->getTodayTimeDeviation($employee);
        $checkOutDeviation = $todayDeviation ? $todayDeviation['check_out_deviation'] : 0;

        $officeEnd = Carbon::parse($checkOut->toDateString() . ' ' . $scheduledCheckOut)->addMinutes(intval($checkOutDeviation));

        if ($checkOut->greaterThan($officeEnd)) {
            return 0; // No early leavings
        }

        return (int) floor($officeEnd->diffInSeconds($checkOut, true) / 60);
    }

    protected function calculateOvertimeMinutes(Carbon $checkOut)
    {
        $employee = auth()->user()->employee;
        $today = Carbon::now()->format('l');
        $attendanceDay = EmployeeAttendanceDay::where('employee_id', $employee->id)
            ->where('day', $today)
            ->where('is_working_day', true)
            ->first();
        if(!$attendanceDay){
            return 0;
        }
        $scheduledCheckOut  = $attendanceDay->checkout_time ?? null;

        $todayDeviation = $this->getTodayTimeDeviation($employee);
        $checkOutDeviation = $todayDeviation ? $todayDeviation['check_out_deviation'] : 0;

        $officeEnd = Carbon::parse($checkOut->toDateString() . ' ' . $scheduledCheckOut)->addMinutes(intval($checkOutDeviation));

        if ($checkOut->lessThanOrEqualTo($officeEnd)) {
            return 0; // No overtime
        }

        return (int) floor($checkOut->diffInSeconds($officeEnd, true) / 60);
    }

    public function getEmployeeAttendance(int $employeeId, array $filters = []): LengthAwarePaginator
    {
        $query = Attendance::where('employee_id', $employeeId);
        
        $this->applyFilters($query, $filters);

        // Determine sorting requested by UI (sortBy/orderBy)
        $uiSort = $filters['sortBy'] ?? null;
        $uiOrder = $filters['orderBy'] ?? null;

        // Map UI keys to actual DB columns
        $sortKeyMap = [
            'employee' => 'employees.name',
            'employee.name' => 'employees.name',
            'employee.department.name' => 'departments.name',
            'employee.employee_code' => 'employees.employee_code',
            'employee_code' => 'employees.employee_code',
            'date' => 'attendances.date',
            'check_in' => 'attendances.check_in',
            'check_out' => 'attendances.check_out',
            'status' => 'attendances.status',
            'late_minutes' => 'attendances.late_minutes',
            'early_leaving_minutes' => 'attendances.early_leaving_minutes',
            'overtime_minutes' => 'attendances.overtime_minutes',
        ];

        $orderDir = strtolower($uiOrder) === 'desc' ? 'desc' : 'asc';
        $mappedSort = $uiSort && isset($sortKeyMap[$uiSort]) ? $sortKeyMap[$uiSort] : null;

        $perPage = $filters['per_page'] ?? 10;

        // Handle "All" case by returning all records without pagination
        if ($perPage == -1) {
            $allRecordsQuery = $query->join('employees', 'attendances.employee_id', '=', 'employees.id');

            // if sorting by department, join departments
            if ($mappedSort === 'departments.name') {
                $allRecordsQuery = $allRecordsQuery->leftJoin('departments', 'employees.department_id', '=', 'departments.id');
            }

            if ($mappedSort) {
                $allRecords = $allRecordsQuery
                    ->orderBy($mappedSort, $orderDir)
                    ->select('attendances.*')
                    ->get();
            } else {
                // default ordering
                $allRecords = $allRecordsQuery
                    ->orderBy('attendances.date', 'desc')
                    ->orderBy('employees.name', 'asc')
                    ->select('attendances.*')
                    ->get();
            }

            return new LengthAwarePaginator(
                $allRecords,
                $allRecords->count(),
                $allRecords->count(), // per_page = total count (all records on one page)
                1, // current page = 1
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        }

        // For paginated response, join employees and departments conditionally and apply ordering
        $paginatedQuery = $query->join('employees', 'attendances.employee_id', '=', 'employees.id');

        if ($mappedSort === 'departments.name') {
            $paginatedQuery = $paginatedQuery->leftJoin('departments', 'employees.department_id', '=', 'departments.id');
        }

        if ($mappedSort) {
            $paginatedQuery = $paginatedQuery->orderBy($mappedSort, $orderDir);
        } else {
            // default ordering
            $paginatedQuery = $paginatedQuery->orderBy('attendances.date', 'desc')
                ->orderBy('employees.name', 'asc');
        }

        return $paginatedQuery
            ->select('attendances.*') // select only attendance columns
            ->with('employee') // keep eager loading
            ->paginate($perPage);
    }

    public function getAttendanceByDateRange(array $filters, $isMailing = false): LengthAwarePaginator
    {
        $query = Attendance::with([
            'employee' => fn($q) => $q->withoutGlobalScopes()
                ->where('employment_status_id', 1)
                ->where(function($q) {
                    $q->whereDoesntHave('exemption') // no exemption record
                    ->orWhereHas('exemption', fn($ex) =>
                    $ex->where('attendance_exemption', false) // exemption = false
                    );
                }), 'employee.user'
        ])
            ->whereHas('employee', fn($q) =>
            $q->where('employment_status_id', 1)
                ->where(function($q) {
                    $q->whereDoesntHave('exemption')
                        ->orWhereHas('exemption', fn($ex) =>
                        $ex->where('attendance_exemption', false)
                        );
                })
            );
        if(!$isMailing){
            $query = $query->when(auth()->user()->onlyEmployee(), function ($q) {
                $q->whereHas('employee', fn($q) =>
                $q->where('employees.id', auth()->user()->employee->id)
                    ->where('employment_status_id', 1)
                    ->where(function($q) {
                        $q->whereDoesntHave('exemption')
                            ->orWhereHas('exemption', fn($ex) =>
                            $ex->where('attendance_exemption', false)
                            );
                    })
                );
            })->when(auth()->user()->hasRole('Manager') &&  !auth()->user()->hasRole(['Hr']), function ($q) {
                $q->where(function ($whereQuery) {
                    $whereQuery->whereHas('employee', fn($employeeQuery) =>
                    $employeeQuery->where('employees.id', auth()->user()->employee->id)
                        ->where('employment_status_id', 1)
                        ->where(function($q) {
                            $q->whereDoesntHave('exemption')
                                ->orWhereHas('exemption', fn($ex) =>
                                $ex->where('attendance_exemption', false)
                                );
                        })
                    )
                        ->orWhereHas('employee', fn($employeeQuery) =>
                        $employeeQuery->where('employees.reporting_to', auth()->user()->employee->id)
                            ->where('employment_status_id', 1)
                            ->where(function($q) {
                                $q->whereDoesntHave('exemption')
                                    ->orWhereHas('exemption', fn($ex) =>
                                    $ex->where('attendance_exemption', false)
                                    );
                            })
                        );
                });
            });
        }
        $this->applyFilters($query, $filters);

        // Apply sorting if requested by UI
        $uiSort = $filters['sortBy'] ?? null;
        $uiOrder = $filters['orderBy'] ?? null;

        $sortKeyMap = [
            'employee' => 'employees.name',
            'employee.name' => 'employees.name',
            'employee.employee_code' => 'employees.employee_code',
            'employee_code' => 'employees.employee_code',
            'date' => 'attendances.date',
            'check_in' => 'attendances.check_in',
            'check_out' => 'attendances.check_out',
            'status' => 'attendances.status',
            'late_minutes' => 'attendances.late_minutes',
            'early_leaving_minutes' => 'attendances.early_leaving_minutes',
            'overtime_minutes' => 'attendances.overtime_minutes',
        ];

        // Normalize per-page
        $perPage = $filters['per_page'] ?? 10;

        $sorts = [];
        if ($uiSort) {
            if (is_array($uiSort)) {
                $sorts = $uiSort;
            } else {
                $sorts = array_filter(array_map('trim', explode(',', $uiSort)));
            }
        }

        $orders = [];
        if ($uiOrder) {
            if (is_array($uiOrder)) {
                $orders = $uiOrder;
            } else {
                $orders = array_filter(array_map('trim', explode(',', $uiOrder)));
            }
        }

        // Map UI sort keys to DB columns
        $mappedSorts = [];
        foreach ($sorts as $s) {
            if (isset($sortKeyMap[$s])) {
                $mappedSorts[] = $sortKeyMap[$s];
            }
        }

        if ($perPage == -1) {
            $allRecordsQuery = $query->join('employees', 'attendances.employee_id', '=', 'employees.id');

            if (!empty($mappedSorts)) {
                foreach ($mappedSorts as $i => $col) {
                    $dir = strtolower($orders[$i] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                    $allRecordsQuery = $allRecordsQuery->orderBy($col, $dir);
                }
            } else {
                // default ordering
                $allRecordsQuery = $allRecordsQuery->orderBy('attendances.date', 'desc')
                    ->orderBy('employees.name', 'asc');
            }

            $allRecords = $allRecordsQuery->select('attendances.*')->get();
            // Create a custom paginator that shows all records on one page
            return new LengthAwarePaginator(
                $allRecords,
                $allRecords->count(),
                $allRecords->count(), // per_page = total count (all records on one page)
                1, // current page = 1
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        }

        // Paginated response
        $paginatedQuery = $query->join('employees', 'attendances.employee_id', '=', 'employees.id');

        if (!empty($mappedSorts)) {
            foreach ($mappedSorts as $i => $col) {
                $dir = strtolower($orders[$i] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                $paginatedQuery = $paginatedQuery->orderBy($col, $dir);
            }
        } else {
            $paginatedQuery = $paginatedQuery->orderBy('attendances.date', 'desc')
                ->orderBy('employees.name', 'asc');
        }

        return $paginatedQuery
            ->select('attendances.*') // select only attendance columns
            ->with('employee') // keep eager loading
            ->paginate($perPage);
    }

    public function getAttendanceForReport(array $filters, $isMailing = false): array
    {

        $query = Attendance::query()
            ->whereHas('employee', function ($q) {
                $q->withoutGlobalScopes()
                    ->where('employment_status_id', 1)
                    ->where(function ($q) {
                        $q->whereDoesntHave('exemption')
                            ->orWhereHas('exemption', fn ($ex) => $ex->where('attendance_exemption', false));
                    });
            })
            // ✅ Load relations for names + department grouping
            ->with([
                'employee' => function ($q) {
                    $q->withoutGlobalScopes()
                        ->select(['id', 'name', 'department_id', 'reporting_to', 'employment_status_id']);
                },
                'employee.department' => function ($q) {
                    $q->select(['id', 'name']);
                },
            ]);

        if(!$isMailing){
            $user = auth()->user();
            $empId = $user->employee?->id;
            $query = $query->when($user->onlyEmployee() && $empId, fn ($q) =>
                $q->where('employee_id', $empId)
            )
            ->when($user->hasRole('Manager') && !$user->hasRole('Hr'), function ($q) use ($empId) {
                $q->where(function ($q) use ($empId) {
                    $q->where('employee_id', $empId)
                        ->orWhereHas('employee', fn ($e) => $e->where('reporting_to', $empId));
                });
            });
        }

        $this->applyFilters($query, $filters);

        // ✅ Fetch without joins
        $records = $query
            ->orderByDesc('date')
            ->get();

        // Group by date first
        $groupedByDate = $records->groupBy('date')->map(function ($dateRecords, $date) {

            // Sort by department name (optional; since no join)
            $dateRecords = $dateRecords->sortBy(function ($r) {
                return $r->employee?->department?->name ?? '';
            });

            // Group by department name via relation
            $byDept = $dateRecords->groupBy(function ($r) {
                return $r->employee?->department?->name ?? 'No Department';
            })->map(function ($deptRecords, $deptName) {

                $statusCounts = [
                    'Total'     => $deptRecords->count(),
                    'present'   => $deptRecords->where('status', 'present')
                        ->filter(fn ($row) =>
                            !preg_match('/\b(home|wfh)\b/i', $row->check_in_other_location ?? '')
                        )
                        ->count(),
                    'absent'    => $deptRecords->where('status', 'absent')->count(),
                    'leave'     => $deptRecords->where('status', 'leave')->count(),
                    'WFH'       => $deptRecords->whereIn('status', ['present', 'late'])
                        ->where('check_in_from', 2)
                        ->filter(fn ($row) =>
                            preg_match('/\b(home|wfh)\b/i', $row->check_in_other_location ?? '')
                        )
                        ->count(),
                    'late'      => $deptRecords->where('status', 'late')
                        ->filter(fn ($row) =>
                            !preg_match('/\b(home|wfh)\b/i', $row->check_in_other_location ?? '')
                        )
                        ->count(),
                    'Half Day'  => $deptRecords->where('status', 'half-leave')->count(),
                    'not-marked'=> $deptRecords->where('status', 'not-marked')->count(),
                ];

                $nameOf = fn ($r) => $r->employee?->name;

                $statusEmployees = [
                    'present'    => $deptRecords->where('status', 'present')->map($nameOf)->filter()->values()->all(),
                    'absent'     => $deptRecords->where('status', 'absent')->map($nameOf)->filter()->values()->all(),
                    'leave'      => $deptRecords->where('status', 'leave')->map($nameOf)->filter()->values()->all(),
                    'WFH'        => $deptRecords->whereIn('status', ['present', 'late'])->where('check_in_from', 2)
                        ->filter(function ($row) {
                            return str_contains(
                                strtolower(trim($row->check_in_other_location ?? '')),
                                'home'
                            );
                        })
                        ->map($nameOf)->filter()->values()->all(),
                    'late'       => $deptRecords->where('status', 'late')->map($nameOf)->filter()->values()->all(),
                    'Half Day'   => $deptRecords->where('status', 'half-leave')->map($nameOf)->filter()->values()->all(),
                    'not-marked' => $deptRecords->where('status', 'not-marked')->map($nameOf)->filter()->values()->all(),
                ];

                return [
                    'department' => $deptName,
                    'counts'     => $statusCounts,
                    'employees'  => $statusEmployees,
                ];
            });

            return [
                'date' => $date,
                'departments' => $byDept->values()->toArray(),
            ];
        });

        return $groupedByDate->values()->toArray();
    }

    public function getAttendanceForReportOld(array $filters): array
    {
        $query = Attendance::with(['employee' => fn($q) => $q->withoutGlobalScopes()
            ->where('employment_status_id', 1)
            ->where(function($q) {
                $q->whereDoesntHave('exemption')
                    ->orWhereHas('exemption', fn($ex) => $ex->where('attendance_exemption', false));
            })
        ])
            ->when(auth()->user()->onlyEmployee(), fn($q) =>
            $q->whereHas('employee', fn($q) =>
            $q->where('employees.id', auth()->user()->employee->id)
            )
            )
            ->when(auth()->user()->hasRole('Manager') && !auth()->user()->hasRole(['Hr']), fn($q) =>
            $q->where(function($whereQuery){
                $whereQuery->whereHas('employee', fn($q) =>
                $q->where('employees.reporting_to', auth()->user()->employee->id)
                );
            })
            );

        $this->applyFilters($query, $filters);

        // Fetch all attendance records without pagination
        $records = $query
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->where('employment_status_id', 1)
            ->orderBy('attendances.date', 'desc')
            ->orderBy('departments.name', 'asc')
            ->select(
                'attendances.*',
                'employees.name as employee_name',
                'departments.name as department_name'
            )
            ->get();

        // Group by date first
        $groupedByDate = $records->groupBy('date')->map(function($dateRecords, $date) {
            // Group by department
            $byDept = $dateRecords->groupBy('department_name')->map(function($deptRecords, $deptName) {
                $statusCounts = [
                    'Total' => $deptRecords->count(),
                    'present' => $deptRecords->where('status', 'present')->count(),
                    'absent' => $deptRecords->where('status', 'absent')->count(),
                    'leave' => $deptRecords->where('status', 'leave')->count(),
                    'WFH' => $deptRecords->where('status', 'WFH')->count(),
                    'late' => $deptRecords->where('status', 'late')->count(),
                    'Half Day' => $deptRecords->where('status', 'Half Day')->count(),
                    'not-marked' => $deptRecords->where('status', 'not-marked')->count(),
                ];

                // List employees under each status
                $statusEmployees = [
                    'present' => $deptRecords->where('status', 'present')->pluck('employee_name')->toArray(),
                    'absent' => $deptRecords->where('status', 'absent')->pluck('employee_name')->toArray(),
                    'leave' => $deptRecords->where('status', 'leave')->pluck('employee_name')->toArray(),
                    'WFH' => $deptRecords->where('status', 'WFH')->pluck('employee_name')->toArray(),
                    'late' => $deptRecords->where('status', 'late')->pluck('employee_name')->toArray(),
                    'Half Day' => $deptRecords->where('status', 'Half Day')->pluck('employee_name')->toArray(),
                    'not-marked' => $deptRecords->where('status', 'not-marked')->pluck('employee_name')->toArray(),
                ];

                return [
                    'department' => $deptName,
                    'counts' => $statusCounts,
                    'employees' => $statusEmployees,
                ];
            });

            return [
                'date' => $date,
                'departments' => $byDept->values()->toArray(),
            ];
        });

        return $groupedByDate->values()->toArray();
    }

    /**
     * Get employee-wise daily attendance for a month
     * @param string $month (format: YYYY-MM)
     * @param int|null $departmentId
     * @return array
     */
    public function getEmployeeDailyAttendanceForMonth($month, $departmentId = null, $search = null)
    {
        $startDate = Carbon::parse($month . '-01');
        $endDate   = $startDate->copy()->endOfMonth();
        $today = Carbon::today();
        $user = auth()->user();
        $empId = $user->employee?->id;

        if ($endDate->gt($today)) {
            $endDate = $today;
        }

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d 00:00:00');
        }

        $employeesQuery = Employee::with('user')
            ->whereNull('deleted_at')
            ->where('employment_status_id', 1);
        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }
        if($search){
            $employeesQuery->where(function ($e) use ($search) {
                $e->where('name', 'ilike', "%{$search}%")
                    ->orWhere('employee_code', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('official_email', 'ilike', "%{$search}%")
                    ->orWhere('personal_email', 'ilike', "%{$search}%")
                    ->orWhere('cnic', 'ilike', "%{$search}%");
            });
        }
        $employees = $employeesQuery
            ->when($user->onlyEmployee() && $empId, fn ($q) =>
                $q->where('id', $empId)
            )
            ->where(function ($q) {
                $q->whereDoesntHave('exemption')
                    ->orWhereHas('exemption', fn ($ex) => $ex->where('attendance_exemption', false));
            })
            ->when($user->hasRole('Manager') && !$user->hasRole('Hr'), function ($q) use ($empId) {
                $q->where(function ($q) use ($empId) {
                    $q->where('id', $empId)->orWhere('reporting_to', $empId);
                });
            })
            ->orderBy('name')
            ->get();

        // Fetch all attendance records for the month
        $attendanceRecords = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy(['employee_id', 'date']);

        $result = [];
        foreach ($employees as $employee) {
            $row = [
                'name' => $employee->name,
                'email' => $employee->official_email ?? $employee->personal_email,
                'employee_code' => $employee->employee_code,
                'profile_picture' => $employee->user->avatar_url ?? null,
                'attendance' => [],
            ];
            foreach ($dates as $date) {
                if (isset($attendanceRecords[$employee->id][$date][0])) {
                    $att = $attendanceRecords[$employee->id][$date][0];
                    switch ($att->status) {
                        case 'present':
                            $status = 'P';
                            break;
                        case 'late':
                            $status = 'LT';
                            break;
                        case 'not-marked':
                            $status = 'NM';
                            break;
                        case 'absent':
                            $status = 'A';
                            break;
                        case 'holiday':
                            $status = 'H';
                            break;
                        case 'leave':
                            $status = 'L';
                            break;
                        case 'short-leave':
                            $status = 'SL';
                            break;
                        case 'half-leave':
                            $status = 'HL';
                            break;
                        case 'shift-awaiting':
                            $status = 'SA';
                            break;
                        default:
                            $status = strtoupper(substr($att->status, 0, 2));
                    }
                } else {
                    $status = '-'; // Use dash for no record
                }
                $row['attendance'][$date] = $status;
            }
            $result[] = $row;
        }
        return [
            'dates' => $dates,
            'employees' => $result,
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        // Date filters
        if (!empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        // Date filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        // Date filters
        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereBetween('date', [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay()
            ]);
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('date', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('date', $filters['year']);
        }

        // Employee relation filters
        if (!empty($filters['employees'])) {
            $query->whereIn('employee_id', $filters['employees']);
        }

        if (!empty($filters['departments'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->whereIn('department_id', $filters['departments']);
            });
        }

        if (!empty($filters['designation_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('designation_id', $filters['designation_id']);
            });
        }

        if (!empty($filters['branch_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['employment_status_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('employment_status_id', $filters['employment_status_id']);
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            if($filters['status'] === 'early-checkout'){
                $query->where('early_leaving_minutes', '>', 0);
            }
            elseif($filters['status'] === 'over-time'){
                $query->where('overtime_minutes', '>', 0);
            }
            elseif($filters['status'] === 'checkout'){
                $query->whereNotNull('attendances.check_out')->whereIn('attendances.status', ['present', 'late']);
            }
            else{
                $query->where('attendances.status', $filters['status']);
            }
        }

        if (!empty($filters['today']) && $filters['today']) {
            $today = Carbon::now('Asia/Karachi')->toDateString();
            $query->whereDate('date', $today);
        }

        if (!empty($filters['searchQuery'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('employee', function ($emp) use ($filters) {
                    // Qualify employee columns to avoid ambiguous column errors when outer joins are applied
                    $emp->where('employees.name', 'ilike', "%{$filters['searchQuery']}%")
                        ->orWhere('employees.phone', 'ilike', "%{$filters['searchQuery']}%")
                        ->orWhere('employees.official_email', 'ilike', "%{$filters['searchQuery']}%")
                        ->orWhere('employees.employee_code', 'ilike', "%{$filters['searchQuery']}%");
                });
            });
        }
    }

    public function importAttendances(array $data): bool
    {
        DB::beginTransaction();

        try {
            foreach ($data as $record) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $record['employee_id'],
                        'date' => $record['date']
                    ],
                    $record
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function exportAttendances(array $filters): array
    {
        $query = Attendance::with('employee');

        $this->applyFilters($query, $filters);

        return $query->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    public function getAttendanceStats(array $filters = []): array
    {
        $query = Attendance::with('employee.department', 'employee.user')
            ->whereHas('employee', function ($q) {
                $q->where('employment_status_id', 1);
            });
        $this->applyFilters($query, $filters);

        $totalEmployees = Employee::where('employment_status_id', 1)->count();
        $totalAttendance = $query->clone()->count();
        $totalPresent = $query->clone()->where('status', 'present')->count();
        $totalLate = $query->clone()->where('status', 'late')->count();
        $totalLeave = $query->clone()->where('status', 'leave')->count();
        $absentEmployees = $query->clone()->whereIn('status', ['absent'])->get();
        $notMarked = $query->clone()->whereIn('status', ['not-marked'])->get();

        return [
            'total_employees' => $totalEmployees,
            'total_attendance' => $totalAttendance,
            'total_present' => $totalPresent,
            'total_notMarked' => $notMarked->count(),
            'total_absent' => $absentEmployees->count(),
            'total_late' => $totalLate,
            'total_leave' => $totalLeave,
            'notMarked_employees' => $notMarked,
        ];
    }

    public function exportPdfDepartmentBelow($request, $isMailing = false)
    {
        try{
            $filters = is_array($request) ? $request : $request->all();
            $filters['per_page'] = -1; // Get all records
            $filters['sortBy'] = 'date,employee.department.name,employee';
            $filters['orderBy'] = 'asc,asc,asc';

            // Flat attendance rows
            $attendances = $this->getAttendanceByDateRange($filters, $isMailing);
            $attendanceData = AttendanceResource::collection($attendances->items())->toArray(request());

            usort($attendanceData, function ($a, $b) {
                $dateA = $a['date'] ?? '';
                $dateB = $b['date'] ?? '';
                if ($dateA !== $dateB) {
                    return strcmp($dateA, $dateB);
                }

                $deptA = mb_strtolower($a['department'] ?? '');
                $deptB = mb_strtolower($b['department'] ?? '');
                if ($deptA !== $deptB) {
                    return strcmp($deptA, $deptB);
                }

                $nameA = mb_strtolower($a['employee_name'] ?? '');
                $nameB = mb_strtolower($b['employee_name'] ?? '');
                return strcmp($nameA, $nameB);
            });

            // Grouped department counts per date
            $grouped = $this->getAttendanceForReport($filters, $isMailing);
            $groupedMap = [];
            foreach ($grouped as $g) {
                $key = Carbon::parse($g['date'])->format('d-m-Y');
                $groupedMap[$key] = $g;
            }

            $data = [
                'attendances' => $attendanceData,
                'groupedMap' => $groupedMap,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => $attendances->total(),
            ];

            $filename = 'attendance_report_with_department_summary';

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $filename .= '_' . $filters['start_date'] . '_to_' . $filters['end_date'];
            } elseif (!empty($filters['start_date'])) {
                $filename .= '_from_' . $filters['start_date'];
            } elseif (!empty($filters['end_date'])) {
                $filename .= '_until_' . $filters['end_date'];
            }
            if (!empty($filters['status'])) {
                $filename .= '_' . $filters['status'];
            }

            // Generate PDF using the new blade view
            $pdf = Pdf::loadView('hrm::attendance.pdf-export-dept-below', $data);
            $pdf->setPaper('A4', 'portrait');

            return [
                'file' => $pdf,
                'filename' => $filename . '.pdf'
            ];
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate department-below PDF: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getAttendanceWeeklyReport(array $filters = [], $user = null)
    {
        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;
        // Determine timezone and default date range (last 28 days) when not provided
        $tz = 'Asia/Karachi';

        if (empty($start) || empty($end)) {
            $endDate = Carbon::now($tz)->endOfDay();
            $startDate = Carbon::now($tz)->subDays(27)->startOfDay();
        } else {
            $startDate = Carbon::parse($start, $tz)->startOfDay();
            $endDate = Carbon::parse($end, $tz)->endOfDay();
        }

        $empId = $user?->employee?->id ?? null;

        $query = Attendance::with('employee')
            ->whereHas('employee', function ($q) {
                $q->whereNull('deleted_at')
                  ->where('employment_status_id', 1)
                    ->where(function ($q) {
                        $q->whereDoesntHave('exemption')
                            ->orWhereHas('exemption', fn($ex) => $ex->where('attendance_exemption', false));
                    });
            })
            ->when($user && $user->onlyEmployee() && $empId, fn($q) => $q->where('employee_id', $empId))
            ->when($user && $user->hasRole('Manager') && !$user->hasRole(['Hr']), function ($q) use ($empId) {
                $q->where(function ($w) use ($empId) {
                    $w->where('employee_id', $empId)
                      ->orWhereHas('employee', fn($e) => $e->where('reporting_to', $empId));
                });
            })
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

        if (!empty($filters['department_id'])) {
            $query = $query->whereHas('employee', fn($q) => $q->where('department_id', $filters['department_id']));
        }

        // Text search filter (q): search on employee fields via relation, avoid ambiguous columns
        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $query = $query->whereHas('employee', function ($emp) use ($term) {
                $emp->where(function ($w) use ($term) {
                    $w->where('employees.name', 'ilike', "%$term%")
                      ->orWhere('employees.phone', 'ilike', "%$term%")
                      ->orWhere('employees.official_email', 'ilike', "%$term%")
                      ->orWhere('employees.personal_email', 'ilike', "%$term%")
                      ->orWhere('employees.employee_code', 'ilike', "%$term%");
                });
            });
        }

        $rows = $query->select(['id', 'employee_id', 'date', 'status', 'late_minutes', 'check_in', 'check_out'])->orderBy('date')->get();

        // Build employees list (visibility + exemption rules)
        $employeesQuery = Employee::query()
            ->whereNull('deleted_at')
            ->where('employment_status_id', 1)
            ->where(function ($q) {
                $q->whereDoesntHave('exemption')
                  ->orWhereHas('exemption', fn($ex) => $ex->where('attendance_exemption', false));
            });

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $employeesQuery->where(function ($w) use ($term) {
                $w->where('name', 'ilike', "%$term%")
                    ->orWhere('phone', 'ilike', "%$term%")
                    ->orWhere('official_email', 'ilike', "%$term%")
                    ->orWhere('personal_email', 'ilike', "%$term%")
                    ->orWhere('employee_code', 'ilike', "%$term%");
            });
        }

        if (!empty($filters['department_id'])) {
            $employeesQuery->where('department_id', $filters['department_id']);
        }
        if ($user && $user->onlyEmployee()) {
            $empIdLocal = $user->employee?->id ?? null;
            if ($empIdLocal) $employeesQuery->where('id', $empIdLocal);
        } elseif ($user && $user->hasRole('Manager') && !$user->hasRole(['Hr'])) {
            $empIdLocal = $user->employee?->id ?? null;
            $employeesQuery->where(function ($q) use ($empIdLocal) {
                $q->where('id', $empIdLocal)->orWhere('reporting_to', $empIdLocal);
            });
        }
        $employeesList = $employeesQuery->with('department', 'user')->orderBy('name')->get();

        // Fallback: if no employees fetched but we have attendance rows, derive employees from rows
        if ($employeesList->isEmpty() && $rows->isNotEmpty()) {
            $derived = [];
            foreach ($rows->groupBy('employee_id') as $empId => $items) {
                $first = $items->first();
                $empRel = $first->employee; // may include department via with('employee') on $query
                $derived[] = (object) [
                    'id' => $empRel?->id ?? $empId,
                    'name' => $empRel?->name ?? ('Employee #' . $empId),
                    'employee_code' => $empRel?->employee_code ?? null,
                    'profile_picture' => $empRel && $empRel->user ? ($empRel->user->avatar_url ?? null) : null,
                    'official_email' => $empRel?->official_email ?? null,
                    'personal_email' => $empRel?->personal_email ?? null,
                    'department' => $empRel?->department?->name ?? null,
                ];
            }
            $employeesList = collect($derived);
        }

        // Group by ISO week (year-week)
        $grouped = $rows->groupBy(function ($row) {
            $d = Carbon::parse($row->date);
            return $d->format('o-W'); // ISO week-year and week number
        });

        $summary = [];
        foreach ($grouped as $weekKey => $items) {
            $first = Carbon::parse($items->first()->date)->startOfWeek();
            $last = Carbon::parse($items->first()->date)->endOfWeek();

            $totalEmployees = $items->pluck('employee_id')->unique()->count();

            $present = 0; $absent = 0; $late = 0;
            $approvedLeaves = 0; $fullLeaves = 0; $halfLeaves = 0; $shortLeaves = 0;
            $presentOnTime = 0; $presentOrLate = 0;

            foreach ($items as $row) {
                $status = strtolower(trim($row->status ?? ''));

                if ($status === 'present') {
                    $present++;
                    $presentOrLate++;
                    if ((int)($row->late_minutes ?? 0) === 0) {
                        $presentOnTime++;
                    }
                } elseif ($status === 'late') {
                    $late++;
                    $presentOrLate++;
                } elseif ($status === 'absent') {
                    $absent++;
                }

                if (str_contains($status, 'leave')) {
                    $approvedLeaves++;
                }
                if (str_contains($status, 'full')) {
                    $fullLeaves++;
                }
                if (str_contains($status, 'half-leave')) {
                    $halfLeaves++;
                }
                if (str_contains($status, 'short-leave')) {
                    $shortLeaves++;
                }
            }

            $onTimePct = $presentOrLate > 0 ? round(($presentOnTime / $presentOrLate) * 100, 1) : null;

            $summary[] = [
                'week' => $weekKey,
                'start_date' => $first->format('Y-m-d'),
                'end_date' => $last->format('Y-m-d'),
                'total_employees' => $totalEmployees,
                'present_count' => $present,
                'present_or_late_count' => $presentOrLate,
                'absent_count' => $absent,
                'approved_leaves' => $approvedLeaves,
                'full_leaves' => $fullLeaves,
                'half_leaves' => $halfLeaves,
                'short_leaves' => $shortLeaves,
                'late_count' => $late,
                'on_time_percentage' => $onTimePct,
            ];
        }

        // Sort by week key descending
        usort($summary, function ($a, $b) {
            return strcmp($b['week'], $a['week']);
        });

        // Department-wise summary across the selected date range
        $deptMap = [];
        foreach ($rows as $r) {
            $emp = $r->employee ?? null;
            $deptName = $emp && $emp->department ? ($emp->department->name ?? 'Unassigned') : 'Unassigned';
            $eId = $emp->id ?? ($r->employee_id ?? null);

            if (!isset($deptMap[$deptName])) {
                $deptMap[$deptName] = [
                    'department' => $deptName,
                    'employeeIds' => [],
                    'present_count' => 0,
                    'late_count' => 0,
                    'absent_count' => 0,
                    'leaves' => 0,
                    'half_leaves' => 0,
                    'short_leaves' => 0,
                    'present_on_time' => 0,
                    'present_or_late' => 0,
                    'working_minutes' => 0,
                ];
            }

            if ($eId) $deptMap[$deptName]['employeeIds'][$eId] = true;

            $status = strtolower(trim($r->status ?? ''));
            if ($status === 'present') {
                $deptMap[$deptName]['present_count']++;
                $deptMap[$deptName]['present_or_late']++;
                if ((int)($r->late_minutes ?? 0) === 0) {
                    $deptMap[$deptName]['present_on_time']++;
                }
            } elseif ($status === 'late') {
                $deptMap[$deptName]['late_count']++;
                $deptMap[$deptName]['present_or_late']++;
            } elseif ($status === 'absent') {
                $deptMap[$deptName]['absent_count']++;
            }

            if (str_contains($status, 'leave')) {
                $deptMap[$deptName]['leaves']++;
            }
            if (str_contains($status, 'half')) {
                $deptMap[$deptName]['half_leaves']++;
                $deptMap[$deptName]['leaves']++;
            }
            if (str_contains($status, 'short')) {
                $deptMap[$deptName]['short_leaves']++;
                $deptMap[$deptName]['leaves']++;
            }

            // accumulate working minutes robustly
            if (!empty($r->check_in) && !empty($r->check_out)) {
                try {
                    $ci = (string) $r->check_in;
                    $co = (string) $r->check_out;
                    $d  = (string) $r->date;
                    $hasDateInCI = str_contains($ci, 'T') || preg_match('/\d{4}-\d{2}-\d{2}/', $ci);
                    $hasDateInCO = str_contains($co, 'T') || preg_match('/\d{4}-\d{2}-\d{2}/', $co);
                    $in  = $hasDateInCI ? \Carbon\Carbon::parse($ci) : \Carbon\Carbon::parse($d.' '.$ci, $tz);
                    $out = $hasDateInCO ? \Carbon\Carbon::parse($co) : \Carbon\Carbon::parse($d.' '.$co, $tz);
                    if ($out->greaterThan($in)) {
                        $deptMap[$deptName]['working_minutes'] += (int) floor($out->diffInSeconds($in, true) / 60);
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        $departmentSummary = [];
        foreach ($deptMap as $dn => $vals) {
            $totalEmployees = count($vals['employeeIds']);
            $presentOrLate = ($vals['present_or_late'] ?? 0);
            $onTimeCount = ($vals['present_on_time'] ?? 0);
            $onTimePct = $presentOrLate > 0 ? round(($onTimeCount / $presentOrLate) * 100, 1) : null;

            $departmentSummary[] = [
                'department' => $dn,
                'total_employees' => $totalEmployees,
                'present_count' => ($vals['present_count'] ?? 0) + ($vals['late_count'] ?? 0),
                'present_on_time' => $onTimeCount,
                'on_time_pct' => $onTimeCount,
                'late_count' => $vals['late_count'] ?? 0,
                'absent_count' => $vals['absent_count'] ?? 0,
                'leaves' => $vals['leaves'] ?? 0,
                'half_leaves' => $vals['half_leaves'] ?? 0,
                'short_leaves' => $vals['short_leaves'] ?? 0,
                'on_time_percentage' => $onTimePct,
                'total_working_hours' => round(($vals['working_minutes'] ?? 0) / 60, 2),
            ];
        }

        usort($departmentSummary, function ($a, $b) {
            return strcmp($a['department'] ?? '', $b['department'] ?? '');
        });

        $grand = array_reduce($departmentSummary, function ($carry, $r) {
            $carry['total_employees'] = ($carry['total_employees'] ?? 0) + (int) ($r['total_employees'] ?? 0);
            $carry['present_count'] = ($carry['present_count'] ?? 0) + (int) ($r['present_count'] ?? 0);
            $carry['present_on_time'] = ($carry['present_on_time'] ?? 0) + (int) (data_get($r, 'present_on_time', data_get($r, 'on_time_pct', 0)) ?? 0);
            $carry['late_count'] = ($carry['late_count'] ?? 0) + (int) ($r['late_count'] ?? 0);
            $carry['absent_count'] = ($carry['absent_count'] ?? 0) + (int) ($r['absent_count'] ?? 0);
            $carry['leaves'] = ($carry['leaves'] ?? 0) + (int) ($r['leaves'] ?? 0);
            $carry['half_leaves'] = ($carry['half_leaves'] ?? 0) + (int) ($r['half_leaves'] ?? 0);
            $carry['short_leaves'] = ($carry['short_leaves'] ?? 0) + (int) ($r['short_leaves'] ?? 0);
            // present_or_late is department present_count already (present+late). Do not add late_count again.
            $carry['present_or_late'] = ($carry['present_or_late'] ?? 0) + (int) ($r['present_count'] ?? 0);
            // accumulate working minutes from department hours
            $carry['working_minutes'] = ($carry['working_minutes'] ?? 0) + (int) round(((data_get($r, 'total_working_hours', 0)) * 60));
            return $carry;
        }, []);
        $grand['on_time_percentage'] = ($grand['present_or_late'] ?? 0) > 0
            ? round((($grand['present_on_time'] ?? 0) / ($grand['present_or_late'] ?? 0)) * 100, 1)
            : null;
        $grand['total_working_hours'] = round(($grand['working_minutes'] ?? 0) / 60, 2);

        // Build date list (YYYY-MM-DD)
        $dates = [];
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        $attendanceLookup = [];
        foreach ($rows as $r) {
            $key = ($r->employee_id) . '|' . Carbon::parse($r->date)->format('Y-m-d');
            $attendanceLookup[$key] = [
                'date' => Carbon::parse($r->date)->format('Y-m-d'),
                'check_in' => $r->check_in ?? null,
                'check_out' => $r->check_out ?? null,
                'status' => $r->status ?? null,
                'late_minutes' => (int) ($r->late_minutes ?? 0),
            ];
        }

        $employeesMatrix = [];
        foreach ($employeesList as $emp) {
            $empId = is_object($emp) ? ($emp->id ?? null) : null;
            $empName = is_object($emp) ? ($emp->name ?? null) : null;
            $empCode = is_object($emp) ? ($emp->employee_code ?? null) : null;
            $empEmail = is_object($emp) ? (($emp->official_email ?? null) ?: ($emp->personal_email ?? null)) : null;
            $profilePicture = is_object($emp) ? ($emp->user->avatar_url ?? null) : null;
            $empDept = null;
            if (is_object($emp)) {
                $empDept = method_exists($emp, 'getAttribute') ? ($emp->department->name ?? ($emp->department ?? null)) : ($emp->department ?? null);
            }

            // Aggregates
            $aggPresent = 0;
            $aggAbsent = 0;
            $aggLeave = 0;
            $aggHalfLeave = 0;
            $aggShortLeave = 0;
            $aggWorkingMinutes = 0;
            $aggLateMinutes = 0;
            $aggExtraMinutes = 0;

            $row = [
                'id' => $empId,
                'name' => $empName,
                'employee_code' => $empCode,
                'email' => $empEmail,
                'profile_picture' => $profilePicture,
                'department' => $empDept,
                'attendance' => [],
            ];

            foreach ($dates as $dt) {
                $key = $empId . '|' . $dt;
                if (isset($attendanceLookup[$key])) {
                    $att = $attendanceLookup[$key];
                    $row['attendance'][$dt] = (object) $att;

                    $status = strtolower((string)($att['status'] ?? ''));
                    if ($status === 'present' || $status === 'late') {
                        $aggPresent++;
                    } elseif ($status === 'absent') {
                        $aggAbsent++;
                    } elseif ($status === 'leave') {
                        $aggLeave++;
                    } elseif ($status === 'half-leave') {
                        $aggHalfLeave++;
                    } elseif ($status === 'short-leave') {
                        $aggShortLeave++;
                    }

                    // Working minutes (check_out - check_in)
                    if (!empty($att['check_in']) && !empty($att['check_out'])) {
                        try {
                            $in = Carbon::parse($att['check_in']);
                            $out = Carbon::parse($att['check_out']);
                            if ($out->greaterThan($in)) {
                                $aggWorkingMinutes += (int) floor($out->diffInSeconds($in, true) / 60);
                            }
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }

                    // Late minutes
                    $aggLateMinutes += (int) ($att['late_minutes'] ?? 0);

                    if (!empty($att['check_in']) && !empty($att['check_out'])) {
                        try {
                            $in = Carbon::parse($att['check_in']);
                            $out = Carbon::parse($att['check_out']);
                            if ($out->greaterThan($in)) {
                                $spanMin = (int) floor($out->diffInSeconds($in, true) / 60);
                                if ($spanMin > 540) {
                                    $aggExtraMinutes += ($spanMin - 540);
                                }
                            }
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                } else {
                    $row['attendance'][$dt] = null;
                }
            }

            // Final aggregate formatting
            $row['total_present'] = $aggPresent;
            $row['total_absent'] = $aggAbsent;
            $row['total_leaves'] = $aggLeave;
            $row['half_leaves'] = $aggHalfLeave;
            $row['short_leaves'] = $aggShortLeave;
            $row['total_working_hours'] = round($aggWorkingMinutes / 60, 2);
            $row['late_hours'] = round($aggLateMinutes / 60, 2);
            $row['extra_working_hours'] = round($aggExtraMinutes / 60, 2);

            $employeesMatrix[] = $row;
        }

        return [
            'summary' => $summary,
            'department_summary' => $departmentSummary,
            'department_grand' => $grand,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'dates' => $dates,
            'employees' => $employeesMatrix,
        ];
    }

    public function syncAttendanceStatusForLeave($leave, $who, $status): bool
    {
        DB::beginTransaction();
        try {
//            dd('ok1');
            $attendances = Attendance::where('employee_id', $leave->employee_id)
                ->whereBetween('date', [$leave->start_date, $leave->end_date])
                ->get();
//dd($attendances);
            foreach ($attendances as $attendance) {
//                dd($status);
                // Only update if leave is being rejected
                if ($status === 'rejected') {
//                    dd('ok');
                    if (empty($attendance->check_in)) {
                        // No check-in: mark as absent
                        $attendance->status = 'absent';
                    } else {
                        // Has check-in: determine if shift ended
                        $attendanceDay = $attendance->employee
                            ->attendanceDays()
                            ->where('day', Carbon::parse($attendance->date)->format('l'))
                            ->first();
                        $now = Carbon::now('Asia/Karachi');
                        $shiftEnded = false;
                        if ($attendanceDay && $attendanceDay->checkout_time) {
                            $scheduledCheckout = Carbon::parse($attendance->date . ' ' . $attendanceDay->checkout_time, 'Asia/Karachi');
                            $shiftEnded = $now->greaterThan($scheduledCheckout);
                        }
                        if ($shiftEnded) {
                            // If late_minutes > 0, mark as late, else present
                            $attendance->status = ($attendance->late_minutes > 0) ? 'late' : 'present';
                        } else {
                            $attendance->status = 'not-marked';
                        }
                    }
                    $attendance->save();
                } elseif ($who === 'hr' && $status === 'approve') {
                    $attendance->status = 'leave';
                    $attendance->save();
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getMonthlyEmployeeSummary(array $filters = [])
    {
        $month = Carbon::parse($filters['month'])->format('Y-m');
        $departmentId = $filters['department_id'] ?? null;
        $search = trim($filters['q'] ?? '');
        $tz = 'Asia/Karachi';

        $startDate = Carbon::parse($month . '-01', $tz)->startOfMonth();
        $endDate = (clone $startDate)->endOfMonth();
        if ($endDate->gt(now())) {
            $endDate = now();
        }

        // Build employees set based on access controls
        $user = auth()->user();
        $empId = $user?->employee?->id ?? null;

        $employeesQuery = Employee::query()
            ->whereNull('deleted_at')
            ->where('employment_status_id', 1)
            ->where(function ($q) {
                $q->whereDoesntHave('exemption')
                  ->orWhereHas('exemption', fn($ex) => $ex->where('attendance_exemption', false));
            })
            ->with(['department', 'designation', 'user']);

        if (!empty($search)) {
            $term = $search;
            $employeesQuery->where(function ($w) use ($term) {
                $w->where('name', 'ilike', "%$term%")
                    ->orWhere('phone', 'ilike', "%$term%")
                    ->orWhere('official_email', 'ilike', "%$term%")
                    ->orWhere('personal_email', 'ilike', "%$term%")
                    ->orWhere('employee_code', 'ilike', "%$term%");
            });
        }

        if (!empty($departmentId)) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($user && method_exists($user, 'onlyEmployee') && $user->onlyEmployee() && $empId) {
            $employeesQuery->where('id', $empId);
        } elseif ($user && method_exists($user, 'hasRole') && $user->hasRole('Manager') && !$user->hasRole(['Hr'])) {
            $employeesQuery->where(function ($q) use ($empId) {
                $q->where('id', $empId)->orWhere('reporting_to', $empId);
            });
        }

        $employees = $employeesQuery->orderByDesc('department_id')->orderBy('name')->get();

        // Attendance rows for month
        $attRows = Attendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(['employee_id', 'date', 'status', 'late_minutes', 'check_in', 'check_out', 'check_in_from', 'check_in_other_location', 'check_in_time', 'check_out_time'])
            ->get()
            ->groupBy(['employee_id', function($row){ return Carbon::parse($row->date)->format('Y-m-d'); }]);

        // Build date list (YYYY-MM-DD)
        $dates = [];
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        $rows = [];
        foreach ($employees as $emp) {
            $present = 0; $leave = 0; $wfh = 0; $absent = 0; $late = 0;
            $holiday=0; $notMarked = 0; $shortLeave = 0; $halfDay = 0;
            $allocatedMinutes = 0; $workedMinutes = 0;

            foreach ($dates as $dt) {
                $att = $attRows[$emp->id][$dt][0] ?? null;
                $status = strtolower((string) ($att->status ?? ''));

                // Count attendance statuses
                if (!$att) { $notMarked++; }
                elseif ($status === 'present') { $present++; }
                elseif ($status === 'late') { $late++; }
                elseif ($status === 'holiday') { $holiday++; }
                elseif ($status === 'absent') { $absent++; }
                elseif ($status === 'leave') { $leave++; }
                elseif ($status === 'half-leave') { $halfDay++; }
                elseif ($status === 'short-leave') { $shortLeave++; }
                elseif ($status === 'not-marked') { $notMarked++; }

                // WFH detection
                if ($att && in_array($status, ['present','late']) && (int)($att->check_in_from ?? 0) === 2) {
                    $loc = strtolower(trim($att->check_in_other_location ?? ''));
                    if (str_contains($loc, 'home') || str_contains($loc, 'wfh')) {
                        $wfh++;
                    }
                }
                // Allocated minutes: prefer scheduled times stored on attendance rows
                if ($att && $status !== 'holiday' && $status !== 'leave') {
                    $ci = (string) ($att->check_in_time ?? '');
                    $co = (string) ($att->check_out_time ?? '');

                    if (!empty($ci) && !empty($co)) {
                        try {
                            $hasDateCI = str_contains($ci, 'T') || preg_match('/\d{4}-\d{2}-\d{2}/', $ci);
                            $hasDateCO = str_contains($co, 'T') || preg_match('/\d{4}-\d{2}-\d{2}/', $co);
                            $in = $hasDateCI ? Carbon::parse($ci, $tz) : Carbon::parse($dt . ' ' . $ci, $tz);
                            $out = $hasDateCO ? Carbon::parse($co, $tz) : Carbon::parse($dt . ' ' . $co, $tz);
                            if ($out->greaterThan($in)) {
                                $minutes = (int) floor($out->diffInSeconds($in, true) / 60);
                                if ($status === 'half-leave') {
                                    $minutes = (int) floor($minutes / 2);
                                } elseif ($status === 'short-leave') {
                                    $minutes = (int) floor($minutes / 4);
                                }
                                $allocatedMinutes += $minutes;
                            }
                        } catch (\Exception $e) {
                        }
                    }
                }

                // Worked minutes: from actual attendance check-in/out
                if ($att && !empty($att->check_in) && !empty($att->check_out)) {
                    try {
                        // Support either HH:MM(:SS) or full timestamps
                        $ci = (string) $att->check_in;
                        $co = (string) $att->check_out;
                        $hasDateInCI = str_contains($ci, 'T') || preg_match('/\d{4}-\d{2}-\d{2}/', $ci);
                        $hasDateInCO = str_contains($co, 'T') || preg_match('/\d{4}-\d{2}-\d{2}/', $co);
                        $in  = $hasDateInCI ? Carbon::parse($ci, $tz) : Carbon::parse($dt . ' ' . $ci, $tz);
                        $out = $hasDateInCO ? Carbon::parse($co, $tz) : Carbon::parse($dt . ' ' . $co, $tz);
                        if ($out->greaterThan($in)) {
                            $workedMinutes += (int) floor($out->diffInSeconds($in, true) / 60);
                        }
                    } catch (\Exception $e) { /* ignore */ }
                }

            }

            $rows[] = [
                'name' => $emp->name,
                'employee_code' => $emp->employee_code,
                'profile_picture' => $emp->user ? ($emp->user->avatar_url ?? null) : null,
                'personal_email' => $emp->personal_email,
                'official_email' => $emp->official_email,
                'department' => $emp->department?->name ?? null,
                'designation' => $emp->designation?->title ?? null,
                'total_working_days' => count($dates) - $holiday,
                'present' => $present,
                'total_present' => $present + $late,
                'leave' => $leave,
                'wfh' => $wfh,
                'absent' => $absent,
                'late_arrivals' => $late,
                'not_marked' => $notMarked,
                'half_day' => $halfDay,
                'short_leave' => $shortLeave,
                'allocated_minutes' => $allocatedMinutes,
                'worked_minutes' => $workedMinutes
            ];
        }

        return [
            'rows' => $rows,
            'filters' => [ 'month' => $month, 'department_id' => $departmentId, 'q' => $search ],
        ];
    }
}
