<?php

namespace Modules\HRM\Repositories;

use AllowDynamicProperties;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Mail\LeaveStatusUpdatedMail;
use Modules\Auth\Models\User as AuthUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Mail\LeaveAppliedMail;
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\Leave;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\HRM\Contracts\LeaveRepositoryInterface;
use Modules\HRM\Models\LeaveType;
use Modules\HRM\Traits\File\FileManager;
use Spatie\Permission\Models\Role;
use App\Models\Notification;
use App\Events\NotificationCreated;

#[AllowDynamicProperties]
class LeaveRepository implements LeaveRepositoryInterface
{
    use FileManager;
    public function __construct(AttendanceRepositoryInterface $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }
    public function paginate(array $filters = []): Paginator
    {
        $query = Leave::with(['employee.department' => fn($q) => $q->withoutGlobalScopes(), 'leaveType', 'employee.reportingTo', 'employee.user'])
            ->whereHas('employee', fn($e) => $e->where('employment_status_id', 1))
            ->when(auth()->user()->onlyEmployee(), function ($q) {
                $q->whereHas('employee', fn($q) => $q->where('employees.id', auth()->user()->employee->id));
            })
            ->when(auth()->user()->hasRole('Manager') &&  !auth()->user()->hasRole(['Hr']), function ($q) {
                $q->where(function ($whereQuery) {
                    $whereQuery->whereHas('employee', fn($employeeQuery) => $employeeQuery->where('employees.id', auth()->user()->employee->id))
                        ->orWhereHas('employee', fn($employeeQuery) => $employeeQuery->where('employees.reporting_to', auth()->user()->employee->id));
                });
            })
            ->when(isset($filters['employee_id']), function ($q) use ($filters) {
                $q->where('employee_id', $filters['employee_id']);
            })
            ->when(isset($filters['leave_type_id']), function ($q) use ($filters) {
                $q->where('leave_type_id', $filters['leave_type_id']);
            })
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('manager_status', $filters['status'])
                        ->orWhere('hr_status', $filters['status']);
                });
            })
            ->when(isset($filters['department_id']), function ($query) use ($filters) {
                $query->whereHas('employee', function ($emp) use ($filters) {
                    $emp->where('department_id', $filters['department_id']);
                });
            })
            ->when(isset($filters['from_date']), function ($q) use ($filters) {
                $q->whereDate('start_date', '>=', $filters['from_date']);
            })
            ->when(isset($filters['to_date']), function ($q) use ($filters) {
                $q->whereDate('end_date', '<=', $filters['to_date']);
            })
            ->when(isset($filters['q']) && $filters['q'] !== '', function ($q) use ($filters) {
                $search = $filters['q'];
                $q->where(function ($where) use ($search) {
                    $where->where('leave_reason', 'ilike', "%{$search}%")
                        ->orWhereHas('employee', function ($emp) use ($search) {
                            $emp->where('name', 'ilike', "%{$search}%")
                                ->orWhere('employee_code', 'ilike', "%{$search}%")
                                ->orWhere('official_email', 'ilike', "%{$search}%")
                                ->orWhere('personal_email', 'ilike', "%{$search}%");
                        });
                });
            });
        if (!empty($filters['sortBy']) && !empty($filters['orderBy'])) {
            $sortBy = $filters['sortBy'];
            $orderBy = $filters['orderBy'];

            $allowedSorts = [
                'employee.name' => 'employees.name',
                'employee.employee_code' => 'employees.employee_code',
                'employee.official_email' => 'employees.official_email',
                'employee.personal_email' => 'employees.personal_email',
                'employee.department.name' => 'departments.name',
                'employee.reporting_to.name' => 'employees.reporting_to',
                'leave_type.name' => 'leave_types.name',
                'start_date' => 'leaves.start_date',
                'end_date' => 'leaves.end_date',
                'days' => 'leaves.days',
                'manager_status' => 'leaves.manager_status',
                'hr_status' => 'leaves.hr_status',
                'created_at' => 'leaves.created_at',
                'id' => 'leaves.id',
            ];

            if (!array_key_exists($sortBy, $allowedSorts)) {
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            } else {
                $dbColumn = $allowedSorts[$sortBy];
                $query->select('leaves.*');

                $orderApplied = false;
                if (str_starts_with($dbColumn, 'employees.')) {
                    // employee column (employees.<col>)
                    $col = substr($dbColumn, strlen('employees.'));
                    $query->orderByRaw("(select " . $col . " from employees where employees.id = leaves.employee_id) {$orderBy}");
                    $orderApplied = true;
                } elseif (str_starts_with($dbColumn, 'departments.')) {
                    // department column via employee -> departments.<col>
                    $col = substr($dbColumn, strlen('departments.'));
                    // nested subquery: departments.name where departments.id = (select department_id from employees where employees.id = leaves.employee_id)
                    $query->orderByRaw("(select {$col} from departments where departments.id = (select department_id from employees where employees.id = leaves.employee_id)) {$orderBy}");
                    $orderApplied = true;
                } elseif (str_starts_with($dbColumn, 'leave_types.')) {
                    $col = substr($dbColumn, strlen('leave_types.'));
                    $query->orderByRaw("(select {$col} from leave_types where leave_types.id = leaves.leave_type_id) {$orderBy}");
                    $orderApplied = true;
                }

                if (!$orderApplied) {
                    // simple column on leaves table
                    $query->orderBy($dbColumn, $orderBy);
                }
            }

        } else {
            // Default ordering if no sort specified
            $query->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Leave
    {
        return DB::transaction(function () use ($data) {
            $employee = auth()->user()->employee;

            // Enforce gender-specific leave types at creation time
            if (!empty($data['leave_type_id'])) {
                $lt = LeaveType::find($data['leave_type_id']);
                if ($lt) {
                    $ltName = mb_strtolower($lt->name ?? '');
                    $empGender = mb_strtolower($employee->gender ?? '');
                    if (str_contains($ltName, 'maternity') && $empGender !== 'female') {
                        throw new \Exception('Maternity leave is applicable to female employees only.');
                    }
                    if (str_contains($ltName, 'paternity') && $empGender !== 'male') {
                        throw new \Exception('Paternity leave is applicable to male employees only.');
                    }
                }
            }

            // Validate leave quota before creating
            $this->validateLeaveQuota($data, $employee->id);

            if (auth()->check()) {
                $data['created_by'] = auth()->id();
                $data['updated_by'] = auth()->id();
            }
            if (isset($data['leave_attachment'])) {
                $data['leave_attachment'] = $this->uploadFile($data['leave_attachment'], 'leaves/' . $employee->employee_code);
            }

            $data['employee_id'] = $employee->id;

            // Calculate and set days based on duration type
            $data['days'] = $this->calculateDays($data);


            $managerEmployee = Employee::findOrFail($employee->reporting_to);
            $leave = Leave::create($data);

            $recipientEmails = collect();
            $recipientIds = collect();
            $recipientIds->push($managerEmployee->user_id);

            if (!empty($managerEmployee->official_email)) {
                $recipientEmails->push($managerEmployee->official_email);
            }
            else if (!empty($managerEmployee->personal_email)) {
                $recipientEmails->push($managerEmployee->personal_email);
            }
            else{
                $managerUser = AuthUser::findOrFail($managerEmployee->user_id);
                if (!empty($managerUser->email)) {
                    $recipientEmails->push($managerUser->email);
                }
            }
            try {
                $hrRole = Role::whereIn('name', ['hr', 'HR', 'Hr', 'hR'])->first();
                if ($hrRole) {
                    $hrUsers = $hrRole->users()->whereNotNull('email')->get();
                    foreach ($hrUsers as $hrUser) {
                        $recipientEmails->push($hrUser->email);
                        $recipientIds->push($hrUser->id);
                    }
                }
            } catch (\Exception $e) {
            }
            $recipientEmails = $recipientEmails->unique()->filter();
            if ($recipientEmails->isNotEmpty()) {
                Mail::to($recipientEmails->values()->all())
                    ->send(new LeaveAppliedMail($managerEmployee, $leave));
            }
            if ($recipientIds->isNotEmpty()) {
                foreach ($recipientIds->unique() as $rid) {
                    $notification = Notification::create([
                        'sender_id' => auth()->id(),
                        'receiver_id' => $rid,
                        'title' => 'New Leave Request',
                        'data' => "A new leave request has been submitted by {$employee->name}.",
                        'type' => 'leave_request',
                        'url' => '/hrm/leave/list',
                    ]);
                    $newNotification = Notification::with('employee', 'sender')->findorFail($notification->id);
                    event(new NotificationCreated($newNotification));
                }
            }
            return $leave;
        });
    }

    public function update(int $id, array $data): Leave
    {
        return DB::transaction(function () use ($id, $data) {
            $leave = Leave::findOrFail($id);

            // Enforce gender-specific leave types on update (if leave_type_id provided or existing)
            $ltIdToCheck = $data['leave_type_id'] ?? $leave->leave_type_id;
            if ($ltIdToCheck) {
                $lt = LeaveType::find($ltIdToCheck);
                if ($lt) {
                    $ltName = mb_strtolower($lt->name ?? '');
                    $empGender = mb_strtolower($leave->employee?->gender ?? '');
                    if (str_contains($ltName, 'maternity') && $empGender !== 'female') {
                        throw new \Exception('Maternity leave is applicable to female employees only.');
                    }
                    if (str_contains($ltName, 'paternity') && $empGender !== 'male') {
                        throw new \Exception('Paternity leave is applicable to male employees only.');
                    }
                }
            }

            // Validate leave quota before updating (exclude current leave)
            $this->validateLeaveQuotaForUpdate($data, $leave->employee_id, $id);

            if (auth()->check()) {
                $data['updated_by'] = auth()->id();
            }
            if (isset($data['leave_attachment'])) {
                if ($leave->leave_attachment) {
                    $this->deleteFile($leave->leave_attachment);
                }
                $data['leave_attachment'] = $this->uploadFile($data['leave_attachment'], 'leaves/' . $leave->employee->employee_code);
            }

            // Calculate and set days based on duration type
            $data['days'] = $this->calculateDays($data);

            $leave->update($data);
            return $leave->fresh(['employee', 'leaveType']);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $leave = Leave::findOrFail($id);
            if ($leave->leave_attachment) {
                $this->deleteFile($leave->leave_attachment);
            }
            return (bool) $leave->delete();
        });
    }

    public function find(int $id): Leave
    {
        return Leave::with(['employee', 'leaveType'])->findOrFail($id);
    }

    public function approveRejectByManager(int $id, array $data): Leave
    {
        $leave = Leave::findOrFail($id);
        $leave->manager_status = $data['manager_status'] === 'approve' ? 'approved' : 'rejected';
        $syncLeave = $this->attendanceRepository->syncAttendanceStatusForLeave($leave, who: 'manager', status: $data['manager_status']);
        if(!$syncLeave){
            return $leave;
        }
        $leave->save();

        // Create Notification for Employee
        $employeeUserId = $leave->employee->user_id;
        if ($employeeUserId) {
            $notification = Notification::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $employeeUserId,
                'title' => 'Leave Request ' . ucfirst($leave->manager_status),
                'data' => 'Your leave request has been ' . $leave->manager_status . ' by your manager.',
                'type' => 'leave_status_update',
                'url' => '/hrm/leave/list',
            ]);
            $newNotification = Notification::with('employee', 'sender')->findorFail($notification->id);
            event(new NotificationCreated($newNotification));
        }

        // Send email
        try {
            $actor = auth()->user(); // manager
            $action = $leave->manager_status;

            $employee = $leave->employee;
            $recipient = null;
            if (!empty($employee->official_email)) {
                $recipient = $employee->official_email;
            } elseif (!empty($employee->personal_email)) {
                $recipient = $employee->personal_email;
            }

            if ($recipient) {
                Mail::to($recipient)
                    ->send(new LeaveStatusUpdatedMail($leave, $actor, 'manager', $action));
            }
        } catch (\Exception $e) {
        }
        return $leave;
    }

    public function approveRejectByHr(int $id, array $data): Leave
    {
        return DB::transaction(function () use ($id, $data) {

            $data['hr_status'] = $data['hr_status'] === 'approve'
                ? 'approved'
                : 'rejected';

            $leave = Leave::findOrFail($id);
            $this->attendanceRepository->syncAttendanceStatusForLeave($leave, who: 'hr', status: $data['hr_status']);
            $leave->update($data);

            // Create Notification for Employee
            $employeeUserId = $leave->employee->user_id;
            if ($employeeUserId) {
                $notification = Notification::create([
                    'sender_id' => auth()->id(),
                    'receiver_id' => $employeeUserId,
                    'title' => 'Leave Request ' . ucfirst($data['hr_status']),
                    'data' => 'Your leave request has been ' . $data['hr_status'] . ' by HR.',
                    'type' => 'leave_status_update',
                    'url' => '/hrm/leave/list',
                ]);
                $newNotification = Notification::with('employee', 'sender')->findorFail($notification->id);
                event(new NotificationCreated($newNotification));
            }

            $status = match (true) {
                $leave->days == 0.5 => 'half-leave',
                $leave->days < 0.5  => 'short-leave',
                default             => 'leave',
            };

            if ($data['hr_status'] === 'approved') {
                $attendance = Attendance::where('employee_id', $leave->employee_id)
                    ->whereDate('date', '>=', $leave->start_date->toDateString())
                    ->whereDate('date', '<=', $leave->end_date->toDateString())
                    ->where('status', '!=', 'leave')
                    ->get();

                foreach ($attendance as $att) {
                    $att->update([
                        'status' => $status,
                        'updated_at' => now(),
                    ]);
                }
            }

            // Send email notification to employee about HR's decision; CC manager if available
            try {
                $actor = auth()->user(); // HR user who performed the action
                $employee = $leave->employee;

                $recipientEmails = collect();
                if (!empty($employee->official_email)) {
                    $recipientEmails->push($employee->official_email);
                } elseif (!empty($employee->personal_email)) {
                    $recipientEmails->push($employee->personal_email);
                }

                // Try to add manager email as CC
                $managerEmployee = null;
                try {
                    $managerEmployee = Employee::find($employee->reporting_to);
                } catch (\Exception $e) {
                    $managerEmployee = null;
                }

                if ($managerEmployee) {
                    if (!empty($managerEmployee->official_email)) {
                        $recipientEmails->push($managerEmployee->official_email);
                    } elseif (!empty($managerEmployee->personal_email)) {
                        $recipientEmails->push($managerEmployee->personal_email);
                    } else {
                        $managerUser = AuthUser::find($managerEmployee->user_id);
                        if ($managerUser && !empty($managerUser->email)) {
                            $recipientEmails->push($managerUser->email);
                        }
                    }
                }

                $recipientEmails = $recipientEmails->unique()->filter();
                if ($recipientEmails->isNotEmpty()) {
                    Mail::to($recipientEmails->values()->all())
                        ->send(new \Modules\Auth\Mail\LeaveStatusUpdatedMail($leave, $actor, 'hr', $data['hr_status']));
                }
            } catch (\Exception $e) {
                // swallow exceptions
            }

            return $leave;
        });
    }

    public function getUserLeaveBalance(int $employeeId, int $year = null): array
    {
        // Automatically use current year - no frontend changes needed
        $year = $year ?? (int) date('Y');
        $employee = Employee::query()->select(['id', 'gender'])->find($employeeId);
        $gender = strtolower((string) ($employee->gender ?? ''));

        $leaveTypesQuery = LeaveType::orderBy('sort_order')->orderBy('id');

        // Show Maternity only for females, Paternity only for males (others unchanged)
        if ($gender === 'male') {
            $leaveTypesQuery->where('name', 'not ilike', '%maternity%');
        } elseif ($gender === 'female') {
            $leaveTypesQuery->where('name', 'not ilike', '%paternity%');
        }

        $leaveTypes = $leaveTypesQuery->get();

        $leaveBalance = [];

        foreach ($leaveTypes as $leaveType) {
            // Get approved leaves for this employee and leave type
            // Only count leaves that are approved by both manager and HR
            $approvedPendingLeaves = Leave::where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveType->id)
                ->whereIn('manager_status', ['pending', 'approved'])
                ->whereIn('hr_status', ['pending', 'approved'])
                ->where(function($query) use ($year) {
                    // Include leaves that start in the current year OR end in the current year
                    // This covers cross-year leaves
                    $query->whereYear('start_date', $year)
                          ->orWhereYear('end_date', $year);
                })
                ->get();

            // Calculate total days used for the specific year
            $totalDaysUsed = 0;
            foreach ($approvedPendingLeaves as $leave) {
                $totalDaysUsed += $this->calculateDaysForYear($leave, $year);
            }

            $leaveBalance[] = [
                'leave_type_id' => $leaveType->id,
                'leave_type_name' => $leaveType->name,
                'quota' => $leaveType->quota,
                'used' => round($totalDaysUsed, 2),
                'remaining' => max(0, $leaveType->quota - $totalDaysUsed),
                'percentage_used' => $leaveType->quota > 0 ? round(($totalDaysUsed / $leaveType->quota) * 100, 2) : 0,
                'year' => $year, // Include year in response
            ];
            if($leaveType->name === 'CPL'){
                $cplBalance = Attendance::where('employee_id', 29)
                    ->where('is_holiday_work', true)
                    ->count();
                $lastIndex = count($leaveBalance) - 1;

                $leaveBalance[$lastIndex]['quota'] = $cplBalance;
                $leaveBalance[$lastIndex]['remaining'] = max(0, $cplBalance - $totalDaysUsed);
                $leaveBalance[$lastIndex]['percentage_used'] = $cplBalance > 0
                    ? round(($totalDaysUsed / $cplBalance) * 100, 2)
                    : 0;
            }
        }

        return $leaveBalance;
    }

    /**
     * Calculate days for a specific year from a leave record
     * Handles cross-year leaves by splitting days between years
     */
    private function calculateDaysForYear(Leave $leave, int $year): float
    {
        $startDate = \Carbon\Carbon::parse($leave->start_date);
        $endDate = \Carbon\Carbon::parse($leave->end_date);
        
        // If the leave is entirely within the same year
        if ($startDate->year === $year && $endDate->year === $year) {
            return (float) $leave->days;
        }
        
        // If the leave spans across years, calculate the portion for the requested year
        $yearStart = \Carbon\Carbon::create($year, 1, 1);
        $yearEnd = \Carbon\Carbon::create($year, 12, 31);
        
        // Calculate the overlap between the leave period and the requested year
        $overlapStart = $startDate->max($yearStart);
        $overlapEnd = $endDate->min($yearEnd);
        
        // If there's no overlap with the requested year, return 0
        if ($overlapStart->gt($overlapEnd)) {
            return 0;
        }
        
        // Calculate the total days in the leave period
        $totalLeaveDays = $startDate->diffInDays($endDate) + 1;
        
        // Calculate the overlap days
        $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
        
        // Calculate the proportion of days for this year
        $proportion = $overlapDays / $totalLeaveDays;
        
        // Return the proportional days for this year
        return round((float) $leave->days * $proportion, 2);
    }

    /**
     * Calculate days based on duration type and date range
     */
    private function calculateDays(array $data): float
    {
        if (!isset($data['start_date']) || !isset($data['end_date']) || !isset($data['duration_type'])) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($data['start_date']);
        $endDate = \Carbon\Carbon::parse($data['end_date']);
        $diffDays = $startDate->diffInDays($endDate) + 1; // +1 to include both dates

        switch ($data['duration_type']) {
            case 1: // Full Day
                return (float) $diffDays;
            case 2: // Half Day
                return 0.5;
            case 3: // Short Leave

                // Default to 0.25 days (2 hours) if no hours specified
                return 0.25;
            default:
                return (float) $diffDays;
        }
    }

    /**
     * Validate leave quota for the current year
     */
    private function validateLeaveQuota(array $data, int $employeeId): void
    {
        // Check if required fields are present
        if (!isset($data['leave_type_id'])) {
            throw new \Exception('Leave type is required for quota validation.');
        }

        // Get leave type quota
        $leaveType = LeaveType::find($data['leave_type_id']);
        if (!$leaveType) {
            throw new \Exception('Invalid leave type.');
        }

        // Get current year
        $currentYear = (int) date('Y');

        // Get approved leaves for this employee and leave type for current year
        // Use the same cross-year logic as getUserLeaveBalance
        $approvedLeaves = Leave::where('employee_id', $employeeId)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('manager_status', ['pending', 'approved'])
            ->where('hr_status', ['pending', 'approved'])
            ->where(function($query) use ($currentYear) {
                // Include leaves that start in the current year OR end in the current year
                // This covers cross-year leaves
                $query->whereYear('start_date', $currentYear)
                      ->orWhereYear('end_date', $currentYear);
            })
            ->get();

        // Calculate total days already used for the current year using cross-year logic
        $totalDaysUsed = 0;
        foreach ($approvedLeaves as $leave) {
            $totalDaysUsed += $this->calculateDaysForYear($leave, $currentYear);
        }

        $requestedDays = $this->calculateDays($data);

        // Check if adding requested days would exceed quota
        $totalAfterRequest = $totalDaysUsed + $requestedDays;

        $cplBalance = Attendance::where('employee_id', 29)
            ->where('is_holiday_work', true)
            ->count();

        if ($totalAfterRequest > ($leaveType->name === 'CPL' ? $cplBalance : $leaveType->quota)) {
            $remainingQuota = max(0, $leaveType->quota - $totalDaysUsed);
            throw new \Exception(
                "You are exceeding the quota for {$leaveType->name} for the current year. " .
                "Quota: {$leaveType->quota} days, Used: {$totalDaysUsed} days, " .
                "Remaining: {$remainingQuota} days, Requested: {$requestedDays} days."
            );
        }
    }

    /**
     * Validate leave quota for update (exclude current leave from calculation)
     */
    private function validateLeaveQuotaForUpdate(array $data, int $employeeId, int $currentLeaveId): void
    {
        // Check if required fields are present
        if (!isset($data['leave_type_id'])) {
            throw new \Exception('Leave type is required for quota validation.');
        }

        // Get leave type quota
        $leaveType = LeaveType::find($data['leave_type_id']);
        if (!$leaveType) {
            throw new \Exception('Invalid leave type.');
        }

        // Get current year
        $currentYear = (int) date('Y');

        // Get approved leaves for this employee and leave type for current year
        // Use the same cross-year logic as getUserLeaveBalance
        // EXCLUDE the current leave being updated
        $approvedPendingLeaves = Leave::where('employee_id', $employeeId)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('manager_status', ['pending', 'approved'])
            ->where('hr_status', ['pending', 'approved'])
            ->where(function($query) use ($currentYear) {
                // Include leaves that start in the current year OR end in the current year
                // This covers cross-year leaves
                $query->whereYear('start_date', $currentYear)
                      ->orWhereYear('end_date', $currentYear);
            })
            ->where('id', '!=', $currentLeaveId) // Exclude current leave
            ->get();

        // Calculate total days already used for the current year using cross-year logic
        // (excluding current leave)
        $totalDaysUsed = 0;
        foreach ($approvedPendingLeaves as $leave) {
            $totalDaysUsed += $this->calculateDaysForYear($leave, $currentYear);
        }

        // Calculate requested days for the update
        $requestedDays = $this->calculateDays($data);

        // Check if adding requested days would exceed quota
        $totalAfterRequest = $totalDaysUsed + $requestedDays;

        if ($totalAfterRequest > $leaveType->quota) {
            $remainingQuota = max(0, $leaveType->quota - $totalDaysUsed);
            throw new \Exception(
                "You are exceeding the quota for {$leaveType->name} for the current year. " .
                "Quota: {$leaveType->quota} days, Used: {$totalDaysUsed} days, " .
                "Remaining: {$remainingQuota} days, Requested: {$requestedDays} days."
            );
        }
    }

    public function getLeaveStats()
    {
        $todayDate = now()->format('Y-m-d');
        $thisMonthStart = now()->startOfMonth()->format('Y-m-d');
        $thisMonthEnd = now()->endOfMonth()->format('Y-m-d');

        $thisMonthLeaves = Leave::where(function ($q) use ($thisMonthStart, $thisMonthEnd) {
            $q->whereBetween('leaves.start_date', [$thisMonthStart, $thisMonthEnd])->orWhereBetween('leaves.end_date', [$thisMonthStart, $thisMonthEnd]);
        })->get();

        $thisMonthFullDayLeaves = $thisMonthLeaves->whereBetween('leave_type_id', [1, 5]);
        $thisMonthHalfDayLeaves = $thisMonthLeaves->where('leave_type_id', 6);

        $todayFullDayLeaves = $thisMonthFullDayLeaves->where(function ($item) use ($todayDate) {
            $startDate = Carbon::parse($item->start_date);
            $endDate = Carbon::parse($item->end_date);
            $todayDate = Carbon::parse($todayDate);

            return $startDate->lessThanOrEqualTo($todayDate) && $endDate->greaterThanOrEqualTo($todayDate);
        });

        $todayHalfDayLeaves = $thisMonthHalfDayLeaves->where(function ($item) use ($todayDate) {
            $startDate = Carbon::parse($item->start_date);
            $endDate = Carbon::parse($item->end_date);
            $todayDate = Carbon::parse($todayDate);

            return $startDate->lessThanOrEqualTo($todayDate) && $endDate->greaterThanOrEqualTo($todayDate);
        });

        return [
            'today' => [
                'total_full_day_leaves' => $todayFullDayLeaves->count(),
                'total_half_day_leaves' => $todayHalfDayLeaves->count(),
            ],
            'this_month' => [
                'total_full_day_leaves' => $thisMonthFullDayLeaves->count(),
                'total_half_day_leaves' => $thisMonthHalfDayLeaves->count(),
            ]
        ];
    }

    /**
     * Return all leaves matching filters (no pagination) for exports
     */
    public function export(array $filters = [])
    {
        $query = Leave::with(['employee.department' => fn($q) => $q->withoutGlobalScopes(), 'leaveType'])
            ->whereHas('employee', fn($e) => $e->where('employment_status_id', 1))
            ->when(auth()->user()->onlyEmployee(), function ($q) {
                $q->whereHas('employee', fn($q) => $q->where('employees.id', auth()->user()->employee->id));
            })
            ->when(auth()->user()->hasRole('Manager') &&  !auth()->user()->hasRole(['Hr']), function ($q) {
                $q->where(function ($whereQuery) {
                    $whereQuery->whereHas('employee', fn($employeeQuery) => $employeeQuery->where('employees.id', auth()->user()->employee->id))
                        ->orWhereHas('employee', fn($employeeQuery) => $employeeQuery->where('employees.reporting_to', auth()->user()->employee->id));
                });
            })
            ->when(isset($filters['employee_id']), function ($q) use ($filters) {
                $q->where('employee_id', $filters['employee_id']);
            })
            ->when(isset($filters['leave_type_id']), function ($q) use ($filters) {
                $q->where('leave_type_id', $filters['leave_type_id']);
            })
            ->when(isset($filters['status']), function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('manager_status', $filters['status'])
                        ->orWhere('hr_status', $filters['status']);
                });
            })
            ->when(isset($filters['department_id']), function ($query) use ($filters) {
                $query->whereHas('employee', function ($emp) use ($filters) {
                    $emp->where('department_id', $filters['department_id']);
                });
            })
            ->when(isset($filters['from_date']), function ($q) use ($filters) {
                $q->whereDate('start_date', '>=', $filters['from_date']);
            })
            ->when(isset($filters['to_date']), function ($q) use ($filters) {
                $q->whereDate('end_date', '<=', $filters['to_date']);
            })
            ->when(isset($filters['q']) && $filters['q'] !== '', function ($q) use ($filters) {
                $search = $filters['q'];
                $q->where(function ($where) use ($search) {
                    $where->where('leave_reason', 'ilike', "%{$search}%")
                        ->orWhereHas('employee', function ($emp) use ($search) {
                            $emp->where('name', 'ilike', "%{$search}%")
                                ->orWhere('employee_code', 'ilike', "%{$search}%")
                                ->orWhere('official_email', 'ilike', "%{$search}%")
                                ->orWhere('personal_email', 'ilike', "%{$search}%");
                        });
                });
            });

        if (!empty($filters['sortBy']) && !empty($filters['orderBy'])) {
            $sortBy = $filters['sortBy'];
            $orderBy = $filters['orderBy'];

            // Whitelist mapping: frontend keys -> fully qualified DB columns
            $allowedSorts = [
                // employee fields
                'employee.name' => 'employees.name',
                'employee.employee_code' => 'employees.employee_code',
                'employee.official_email' => 'employees.official_email',
                'employee.personal_email' => 'employees.personal_email',
                // department via employee
                'employee.department.name' => 'departments.name',
                // leave type
                'leave_type.name' => 'leave_types.name',
                // leaves table fields
                'start_date' => 'leaves.start_date',
                'end_date' => 'leaves.end_date',
                'days' => 'leaves.days',
                'manager_status' => 'leaves.manager_status',
                'hr_status' => 'leaves.hr_status',
                'created_at' => 'leaves.created_at',
                'id' => 'leaves.id',
            ];

            // Only allow sorting by whitelisted keys
            if (!array_key_exists($sortBy, $allowedSorts)) {
                // fallback to default ordering
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
            } else {
                $dbColumn = $allowedSorts[$sortBy];

                // For export (no pagination) apply same ORDER BY logic using subqueries to avoid JOIN/GROUP BY
                $query->select('leaves.*');
                $orderApplied = false;
                if (str_starts_with($dbColumn, 'employees.')) {
                    $col = substr($dbColumn, strlen('employees.'));
                    $query->orderByRaw("(select " . $col . " from employees where employees.id = leaves.employee_id) {$orderBy}");
                    $orderApplied = true;
                } elseif (str_starts_with($dbColumn, 'departments.')) {
                    $col = substr($dbColumn, strlen('departments.'));
                    $query->orderByRaw("(select {$col} from departments where departments.id = (select department_id from employees where employees.id = leaves.employee_id)) {$orderBy}");
                    $orderApplied = true;
                } elseif (str_starts_with($dbColumn, 'leave_types.')) {
                    $col = substr($dbColumn, strlen('leave_types.'));
                    $query->orderByRaw("(select {$col} from leave_types where leave_types.id = leaves.leave_type_id) {$orderBy}");
                    $orderApplied = true;
                }
                if (!$orderApplied) {
                    $query->orderBy($dbColumn, $orderBy);
                }
             }

         } else {
             $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
         }

        return $query->get();
    }

    public function getAllLeaveBalances(array $filters = []): Paginator
    {
        $query = Employee::with(['department', 'designation', 'branch', 'user'])
            ->where('employment_status_id', 1)
            ->whereDoesntHave('exemption', function ($q) {
                $q->where('attendance_exemption', true);
            });

        $user = auth()->user();
        if ($user) {
            if ($user->hasRole('Manager') && !$user->hasRole(['Hr'])) {
                $managerEmpId = $user->employee?->id ?? null;
                if ($managerEmpId) {
                    $query->where(function ($q) use ($managerEmpId) {
                        $q->where('id', $managerEmpId)
                          ->orWhere('reporting_to', $managerEmpId);
                    });
                }
            }
            elseif ($user->onlyEmployee()) {
                $empId = $user->employee?->id ?? null;
                if ($empId) {
                    $query->where('id', $empId);
                }
            }
        }

        // Apply filters
        if (!empty($filters['year'])) {
            $query->whereYear('created_at', '<=', $filters['year']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('employee_code', 'ilike', "%{$search}%")
                    ->orWhere('official_email', 'ilike', "%{$search}%")
                    ->orWhere('personal_email', 'ilike', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;
        $sortBy = $filters['sortBy'] ?? null;
        $orderBy = strtolower($filters['orderBy'] ?? 'asc');

        // Default ordering: department then employee name
        if (empty($sortBy)) {
            $query->orderBy('department_id', 'asc')->orderBy('name', 'asc');
        }

        if ($sortBy && str_starts_with($sortBy, 'lt_')) {
            $ltId = (int) substr($sortBy, 3);
            $employees = $query->get();

            // map employees -> items with balances
            $items = $employees->map(function ($employee) use ($filters) {
                $balances = $this->getUserLeaveBalance($employee->id, $filters['year'] ?? null);
                return [
                    'employee' => $employee,
                    'balances' => $balances,
                ];
            });

            // sort by selected leave-type remaining
            $items = $items->sortBy(function ($item) use ($ltId) {
                $b = collect($item['balances'] ?? [])->first(fn($x) => ($x['leave_type_id'] ?? $x->leave_type_id) == $ltId);
                return $b ? ($b['remaining'] ?? ($b->remaining ?? 0)) : 0;
            }, SORT_REGULAR, $orderBy === 'desc');

            $total = $items->count();

            // Apply pagination manually; if per_page == -1 return all
            if ($perPage == -1) {
                $paged = $items->values();
                $perPage = $total ?: 1;
                $currentPage = 1;
            } else {
                $currentPage = max(1, (int) $page);
                $offset = ($currentPage - 1) * $perPage;
                $paged = $items->slice($offset, $perPage)->values();
            }

            return new LengthAwarePaginator(
                $paged,
                $total,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Not lt_ sorting: handle per_page == -1 (export all) or DB-level pagination
        if ($perPage == -1) {
            // get all employees with default db ordering or provided sort
            if (!empty($filters['sortBy']) && !empty($filters['orderBy'])) {
                $allowedSorts = [
                    'name' => 'name',
                    'employee_code' => 'employee_code',
                    'department' => 'department_id',
                    'designation' => 'designation_id',
                ];
                if (array_key_exists($filters['sortBy'], $allowedSorts)) {
                    $query->orderBy($allowedSorts[$filters['sortBy']], $orderBy);
                }
            }

            $employees = $query->get();

            $items = $employees->map(function ($employee) use ($filters) {
                $balances = $this->getUserLeaveBalance($employee->id, $filters['year'] ?? null);
                return [
                    'employee' => $employee,
                    'balances' => $balances,
                ];
            });

            $total = $items->count();

            return new LengthAwarePaginator(
                $items->values(),
                $total,
                $total ?: 1,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Default DB-level sorting when possible
        $allowedSorts = [
            'name' => 'name',
            'employee_code' => 'employee_code',
            'department' => 'department_id',
            'designation' => 'designation_id',
        ];

        if ($sortBy && array_key_exists($sortBy, $allowedSorts)) {
            $dbCol = $allowedSorts[$sortBy];
            $query->orderBy($dbCol, $orderBy);
        }

        $employees = $query->paginate($perPage);

        // Map employees to leave balances
        $items = $employees->getCollection()->map(function ($employee) use ($filters) {
            $balances = $this->getUserLeaveBalance($employee->id, $filters['year'] ?? null);
            return [
                'employee' => $employee,
                'balances' => $balances,
            ];
        });

        // Return a paginator with mapped items
        $paginator = new LengthAwarePaginator(
            $items,
            $employees->total(),
            $employees->perPage(),
            $employees->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginator;
    }
}
