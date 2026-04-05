<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HRM\Models\Attendance;
use Modules\HRM\Models\Employee;
use App\Models\PersonalAccessToken;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\EmployeeRequest;
use Modules\HRM\Http\Resources\EmployeeResource;
use Modules\HRM\Contracts\EmployeeRepositoryInterface;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\HRM\Exports\EmployeesExport;
use Modules\HRM\Http\Resources\LoggedInEmployeeResource;
use Modules\HRM\Models\Branch;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    protected $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function employeeByRules(Request $request)
    {
        $employees = $this->employeeRepository->getByRules($request->all());
        return $employees;
    }

    public function index(Request $request)
    {
        if ($request->has('only_managers') && $request->only_managers) {
            return $this->getManagers($request);
        }

        $employees = $this->employeeRepository->paginate($request->all());
        return EmployeeResource::collection($employees);
    }

    public function getAllEmployees(Request $request)
    {
        $employees = $this->employeeRepository->getAll($request->all());
        return EmployeeResource::collection($employees);
    }

    public function getManagers(Request $request)
    {
        $managers = $this->employeeRepository->getManagers($request);
        return EmployeeResource::collection($managers);
    }

    public function getRfqManagers(Request $request)
    {
        $managers = $this->employeeRepository->getRfqManagers($request->all());
        return EmployeeResource::collection($managers);
    }

    public function store(EmployeeRequest $request)
    {
        $employee = $this->employeeRepository->create($request->validated());
        $today = now()->toDateString();
        $dayName = Carbon::now()->format('l');
        [$scheduledCheckIn, $scheduledCheckOut] = $this->resolveScheduledTimes($employee, $today, $dayName);
        Attendance::create(
            [
                'employee_id' => $employee->id,
                'date'        => $today,
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

        $employee = new EmployeeResource($employee->load([
            'department',
            'designation',
            'branch',
            'user',
            'employmentType',
            'employmentStatus'
        ]));

        return response()->json(['status' => 'success', 'message' => 'Employee created successfully', 'data' => $employee], 201);
    }

    private function resolveScheduledTimes(Employee $employee, string $date, string $dayName): array
    {
        $attendanceDay = $employee->attendanceDays()
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

    public function show(Employee $employee)
    {
        return new EmployeeResource($employee->load([
            'department',
            'designation',
            'branch',
            'dependents',
            'assets.assetType',
            'jobCategory',
            'jobStage',
            'terminationType',
            'reportingTo',
            'employmentType',
            'employmentStatus',
            'documents.documentType',
            'attendanceDays'
        ]));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $validatedData = $request->validated();
        $employee = $this->employeeRepository->update($employee->id, $validatedData);

        return new EmployeeResource($employee->load([
            'department',
            'designation',
            'branch',
            'user',
            'employmentType',
            'employmentStatus'
        ]));
    }

    public function destroy(Employee $employee)
    {
        $this->employeeRepository->delete($employee->id);
        return response()->json(['message' => 'Employee deleted successfully']);
    }

    public function restore($id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $this->employeeRepository->restore($employee->id);

        return response()->json(['message' => 'Employee restored successfully']);
    }

    public function forceDelete($id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $this->employeeRepository->forceDelete($employee->id);

        return response()->json(['message' => 'Employee permanently deleted']);
    }

    public function getRolesByEmployee(Employee $employee)
    {
        return $employee->user->roles;
    }

    public function assignRoles(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'role_ids' => 'required|array',
        ]);

        $this->employeeRepository->assignRoles($request->only([
            'employee_id',
            'role_ids',
        ]));

        return response()->json(['message' => 'Roles assigned successfully']);
    }
    public function updateAttExemption(Request $request)
    {
        $request->validate([
            'attendance_exemption' => 'required|boolean',
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        $this->employeeRepository->updateExemption($request->only([
            'employee_id',
            'attendance_exemption',
        ]));

        return response()->json(['message' => 'Roles assigned successfully']);
    }

    public function withRoles(Request $request)
    {
        $employees = $this->employeeRepository->paginateWithRoles($request->all());
        return EmployeeResource::collection($employees);
    }

    public function loggedIn(Request $request)
    {
        $query = PersonalAccessToken::query()
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('personal_access_tokens')
                    ->groupBy('tokenable_id');
            })
            ->whereHas('tokenable')
            ->with([
                'tokenable.employee.reportingTo',
                'tokenable.employee.department',
                'tokenable.roles',
                'loginActivity',
            ]);

        $query = $this->applyFilters($query, $request);

        $perPage = $request->input('per_page', 10);
        $loggedInUsers = $query->paginate($perPage);
//        return $loggedInUsers;
        return LoggedInEmployeeResource::collection($loggedInUsers);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($qq) use ($search) {

                // A) tokenable has NO employee => search in user table
                $qq->where(function ($noEmp) use ($search) {
                    $noEmp->whereDoesntHave('tokenable.employee')
                        ->whereHas('tokenable', function ($userQ) use ($search) {
                            $userQ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%"); // optional
                        });
                })

                    // B) tokenable HAS employee => search in employee fields
                    ->orWhereHas('tokenable.employee', function ($empQ) use ($search) {
                        $empQ->where(function ($e) use ($search) {
                            $e->where('name', 'ilike', "%{$search}%")
                                ->orWhere('employee_code', 'ilike', "%{$search}%")
                                ->orWhere('phone', 'ilike', "%{$search}%")
                                ->orWhere('cnic', 'ilike', "%{$search}%");
                        });
                    });

            });
        }

        // 2) Employee-based filters (keep admins)
        $employeeFilters = [
            'department_id',
            'employment_type_id',
            'employment_status_id',
        ];

        foreach ($employeeFilters as $field) {
            if ($request->filled($field)) {
                $value = $request->input($field);

                $query->where(function ($qq) use ($field, $value) {
                    $qq->whereDoesntHave('tokenable.employee') // keep admins
                    ->orWhereHas('tokenable.employee', function ($empQ) use ($field, $value) {
                        $empQ->where($field, $value);
                    });
                });
            }
        }

//        if ($request->filled('user_status')) {
//            $query->whereHas('tokenable.employee.user', function ($q) use ($request) {
//                $q->where('status', $request->user_status);
//            });
//        }

        return $query;
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->all();
        $filters['per_page'] = -1;
        $employees = $this->employeeRepository->getByRulesForExport($filters);

        $data = [
            'employees' => $employees,
            'filters' => $filters,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $employees->count(),
        ];

        $filename = 'employee_rules_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new EmployeesExport($data), $filename);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->all();
        $filters['per_page'] = -1;
        $employees = $this->employeeRepository->getByRulesForExport($filters);

        $data = [
            'employees' => $employees,
            'filters' => $filters,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => $employees->count(),
        ];

        $filename = 'employee_rules_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('hrm::employee_rules.pdf-export', $data);
        return $pdf->download($filename);
    }
}
