<?php

namespace Modules\HRM\Repositories;

use App\Models\User;
use Modules\HRM\Models\Role;
use Modules\HRM\Models\Asset;
use Modules\HRM\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\CRM\Models\AssignedManager;
use Modules\HRM\Models\EmployeeDocument;
use Modules\HRM\Traits\File\FileManager;
use Modules\HRM\Models\EmployeeExemption;
use Illuminate\Contracts\Pagination\Paginator;
use Modules\HRM\Contracts\EmployeeRepositoryInterface;
use Modules\HRM\Models\AssetAssignmentHistory;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    use FileManager;

    private const FILE_FIELDS = [
        'photo',
        'cnic_document',
        'resume',
        'experience_letter',
        'offer_letter',
        'contract'
    ];

    public function paginate(array $filters = []): Paginator
    {
        $query = Employee::with(['department', 'designation', 'branch', 'employmentType', 'employmentStatus', 'reportingTo', 'user']);
        $query = $this->applyFilter($query, $filters);
        return $query->oldest('id')->paginate($filters['per_page'] ?? 15);
    }
    public function getAll(array $filters = [])
    {
        $query = Employee::with(['department', 'designation', 'branch', 'employmentType', 'employmentStatus']);
        $query = $this->applyGivenFilterOnly($query, $filters);
        $query->where('employment_status_id', 1);
        return $query->latest('id')->get();
    }

    public function getByRules(array $filters = [])
    {
        $query = Employee::leftJoin('employee_exemptions as ee', 'employees.id', '=', 'ee.employee_id')
            ->leftJoin('departments as d', 'employees.department_id', '=', 'd.id')
            ->leftJoin('designations as des', 'employees.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'employees.employment_type_id', '=', 'et.id')
            ->leftJoin('branches as b', 'employees.branch_id', '=', 'b.id')
            ->where('employees.employment_status_id', 1)
            ->select(
                'employees.id',
                'employees.name',
                'employees.employee_code',
                'employees.cnic',
                'employees.official_email',
                'employees.personal_email',
                'd.name as department',
                'des.title as designation',
                'et.name as employement_type',
                'b.name as branch',
                DB::raw('COALESCE(ee.attendance_exemption, false) as attendance_exemption')
            );
        if (!empty($filters['sortBy']) && !empty($filters['orderBy'])) {
            $query->orderBy($filters['sortBy'], $filters['orderBy']);
        }
        $query = $this->applyFilter($query, $filters);
        $exemptedEmployees = $query->latest('employees.id')
            ->paginate($filters['per_page'] ?? 15);

        return $exemptedEmployees;
    }

    public function getByRulesForExport(array $filters = [])
    {
        $query = Employee::leftJoin('employee_exemptions as ee', 'employees.id', '=', 'ee.employee_id')
            ->leftJoin('departments as d', 'employees.department_id', '=', 'd.id')
            ->leftJoin('designations as des', 'employees.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'employees.employment_type_id', '=', 'et.id')
            ->leftJoin('branches as b', 'employees.branch_id', '=', 'b.id')
            ->select(
                'employees.id',
                'employees.name',
                'employees.employee_code',
                'd.name as department',
                'des.title as designation',
                'employees.date_of_joining',
                'employees.cnic',
                'et.name as employement_type',
                'b.name as branch',
                'employees.official_email',
                'employees.personal_email',
                'employees.termination_effective_date',
                DB::raw('COALESCE(ee.attendance_exemption, false) as attendance_exemption')
            );

        if (!empty($filters['sortBy']) && !empty($filters['orderBy'])) {
            $query->orderBy($filters['sortBy'], $filters['orderBy']);
        }

        $query = $this->applyFilter($query, $filters);

        return $query->latest('employees.id')->get();
    }

    public function updateExemption($data)
    {
        EmployeeExemption::updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
            ],
            [
                'attendance_exemption' => $data['attendance_exemption'],
            ]
        );
        return true;
    }

    public function paginateWithRoles(array $filters = []): Paginator
    {
        $query = Employee::with(['user', 'reportingTo', 'department']);
        //        $query->whereHas('user', function ($q) {
        //            $q->whereHas('roles', function ($r) {
        //                $r->where('name', '!=', 'Employee');
        //            });
        //        });
        $query = $this->applyFilter($query, $filters);
        return $query->latest('id')->paginate($filters['per_page'] ?? 15);
    }

    public function applyFilter($query, array $filters = [])
    {
        return $query
            ->when(auth()->user()->onlyEmployee(), function ($q) {
                $q->where('employees.id', auth()->user()->employee->id);
            })
            ->when(auth()->user()->hasRole('Manager') &&  !auth()->user()->hasRole(['Hr']), function ($q) {
                $q->whereAny(['employees.id', 'employees.reporting_to'], '=', auth()->user()->employee->id);
            })
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny([
                    'employees.name',
                    'employees.personal_email',
                    'employees.official_email',
                    'employees.employee_code',
                ], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['department_id']), function ($query) use ($filters) {
                $query->where('employees.department_id', $filters['department_id']);
            })
            ->when(isset($filters['designation_id']), function ($query) use ($filters) {
                $query->where('employees.designation_id', $filters['designation_id']);
            })
            ->when(isset($filters['employment_status_id']), function ($query) use ($filters) {
                $query->where('employees.employment_status_id', $filters['employment_status_id']);
            })
            ->when(isset($filters['employment_type_id']), function ($query) use ($filters) {
                $query->where('employees.employment_type_id', $filters['employment_type_id']);
            })
            ->when(isset($filters['branch_id']), function ($query) use ($filters) {
                $query->where('employees.branch_id', $filters['branch_id']);
            })
            ->when(isset($filters['user_status']), function ($query) use ($filters) {
                $query->where('employees.status', $filters['user_status']);
            })
            ->when(isset($filters['for_role_assignment']), function ($query) {
                $query->where('employees.id', '<>', auth()->user()->employee->id ?? 0);
            });
    }
    public function applyGivenFilterOnly($query, array $filters = [])
    {
        return $query
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny([
                    'employees.name',
                    'employees.phone',
                    'employees.official_email',
                    'employees.employee_code',
                ], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['department_id']), function ($query) use ($filters) {
                $query->where('employees.department_id', $filters['department_id']);
            })
            ->when(isset($filters['designation_id']), function ($query) use ($filters) {
                $query->where('employees.designation_id', $filters['designation_id']);
            })
            ->when(isset($filters['employment_status_id']), function ($query) use ($filters) {
                $query->where('employees.employment_status_id', $filters['employment_status_id']);
            })
            ->when(isset($filters['employment_type_id']), function ($query) use ($filters) {
                $query->where('employees.employment_type_id', $filters['employment_type_id']);
            })
            ->when(isset($filters['branch_id']), function ($query) use ($filters) {
                $query->where('employees.branch_id', $filters['branch_id']);
            })
            ->when(isset($filters['for_role_assignment']), function ($query) {
                $query->where('employees.id', '<>', auth()->user()->employee->id ?? 0);
            });
    }

    public function getManagers($request): Collection
    {
        // Check if the "Manager" role exists
        if (!Role::where('name', 'Manager')->exists()) {
            return collect();
        }

        $user = auth()->user();

        return Employee::whereHas('user', function ($query) {
            $query->role(['Manager']);
        })
            ->whereNotIn('id', [
                data_get($user, 'employee.id', 0),
                $request->input('for_employee', 0),
            ])->get();
    }

    public function getRfqManagers($data): Collection
    {
        return Employee::whereHas('user.roles.permissions', function ($query) {
            $query->whereIn('name', [
                'rfq_manager.create',
                'rfq_manager.read',
                'rfq_manager.update',
                'rfq_manager.delete',
            ]);
        })
            ->when(isset($data['assigned']), fn($query) => $query->orWhere('id', $data['assigned']))
            ->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Handle file uploads
            foreach (self::FILE_FIELDS as $field) {
                if (isset($data[$field])) {
                    $extension = $data[$field]->getClientOriginalExtension();
                    $data[$field] = $this->uploadFile($data[$field], "employees/{$data['employee_code']}", "$field.$extension");
                }
            }

            $attendanceDays = $data['attendance_days'] ?? [];
            unset($data['attendance_days']);

            // Handle dynamic documents
            $dynamicDocuments = $data['dynamic_documents'] ?? [];
            unset($data['dynamic_documents']);

            // Handle assigned devices
            $assignedDevices = $data['assigned_devices'] ?? [];
            unset($data['assigned_devices']);

            $employee = Employee::create($data);

            // Save attendance days
            foreach ($attendanceDays as $day) {
                $employee->attendanceDays()->create([
                    'day' => $day['day'],
                    'is_working_day' => $day['is_working_day'] ?? false,
                    'inside_office' => $day['is_working_day'] ? $day['inside_office'] : false,
                    'outside_office' => $day['is_working_day'] ? $day['outside_office'] : false,
                    'checkin_time' => ($day['is_working_day'] && ($day['inside_office'] || $day['outside_office'])) ? $day['checkin_time'] : null,
                    'checkout_time' => ($day['is_working_day'] && ($day['inside_office'] || $day['outside_office'])) ? $day['checkout_time'] : null,
                    'allow_late_checkin' => $day['is_working_day'] ? $day['allow_late_checkin'] : false,
                ]);
            }

            // Save dynamic documents
            foreach ($dynamicDocuments as $docData) {
                if (isset($docData['file']) && isset($docData['document_type_id'])) {
                    $extension = $docData['file']->getClientOriginalExtension();
                    $documentTypeName = $docData['document_type_name'] ?? 'document';
                    $documentTypeName = strtolower(str_replace(' ', '_', $documentTypeName));

                    $filePath = $this->uploadFile(
                        $docData['file'],
                        "employees/{$data['employee_code']}/documents",
                        "{$documentTypeName}.{$extension}"
                    );

                    EmployeeDocument::create([
                        'employee_id' => $employee->id,
                        'document_type_id' => $docData['document_type_id'],
                        'file_path' => $filePath
                    ]);
                }
            }

            // Save assigned devices
            foreach ($assignedDevices as $deviceData) {
                if (isset($deviceData['asset_id']) && $deviceData['asset_id']) {
                    $employee->assets()->attach($deviceData['asset_id'], [
                        'assigned_date' => $deviceData['assigned_date'] ?? now()->toDateString()
                    ]);

                    AssetAssignmentHistory::create([
                        'asset_id' => $deviceData['asset_id'],
                        'employee_id' => $employee->id,
                        'assigned_date' => $deviceData['assigned_date'],
                    ]);
                }
            }

            $this->createUserForEmployee($employee, $data);

            return $employee;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $employee = Employee::findOrFail($id);

            foreach (self::FILE_FIELDS as $field) {
                if (isset($data[$field]) && !is_string($data[$field])) {
                    // Delete old file if exists
                    //                    if ($employee->$field) {
                    //                        $this->deleteFile($employee->$field);
                    //                        $data[$field] = null;
                    //                    }
                    $extension = $data[$field]->getClientOriginalExtension();
                    $data[$field] = $this->uploadFile($data[$field], "employees/{$employee->employee_code}", "$field.$extension");
                } elseif ($employee->$field && (isset($data[$field]) && $data[$field] === 'undefined')) {
                    //                    $this->deleteFile($employee->$field);
                    $data[$field] = null;
                } else {
                    // Keep the existing file if not updating
                    $data[$field] = $employee->$field;
                }
            }

            $attendanceDays = $data['attendance_days'] ?? [];
            unset($data['attendance_days']);

            // Handle dynamic documents
            $dynamicDocuments = $data['dynamic_documents'] ?? [];
            unset($data['dynamic_documents']);

            $employee->update($data);

            // Update attendance days: delete old and insert new
            $employee->attendanceDays()->delete();
            foreach ($attendanceDays as $day) {
                $employee->attendanceDays()->create([
                    'day' => $day['day'],
                    'is_working_day' => $day['is_working_day'] ?? false,
                    'inside_office' => $day['is_working_day'] ? $day['inside_office'] : false,
                    'outside_office' => $day['is_working_day'] ? $day['outside_office'] : false,
                    'checkin_time' => ($day['is_working_day'] && ($day['inside_office'] || $day['outside_office'])) ? $day['checkin_time'] : null,
                    'checkout_time' => ($day['is_working_day'] && ($day['inside_office'] || $day['outside_office'])) ? $day['checkout_time'] : null,
                    'allow_late_checkin' => $day['is_working_day'] ? $day['allow_late_checkin'] : false,
                ]);
            }

            // Update dynamic documents: handle both existing and new documents
            if (!empty($dynamicDocuments)) {
                // Get existing documents to preserve those without new files
                $existingDocuments = $employee->documents()->get()->keyBy('id');

                // Process each document in the request
                foreach ($dynamicDocuments as $docData) {
                    if (isset($docData['document_type_id'])) {
                        // Check if this is an existing document being updated
                        if (isset($docData['existing_document_id']) && $existingDocuments->has($docData['existing_document_id'])) {
                            $existingDoc = $existingDocuments->get($docData['existing_document_id']);

                            // If new file is uploaded, update the document
                            if (isset($docData['file']) && $docData['file']) {
                                $extension = $docData['file']->getClientOriginalExtension();
                                $documentTypeName = $docData['document_type_name'] ?? 'document';
                                $documentTypeName = strtolower(str_replace(' ', '_', $documentTypeName));

                                $filePath = $this->uploadFile(
                                    $docData['file'],
                                    "employees/{$employee->employee_code}/documents",
                                    "{$documentTypeName}.{$extension}"
                                );

                                // Update existing document with new file
                                $existingDoc->update([
                                    'document_type_id' => $docData['document_type_id'],
                                    'file_path' => $filePath
                                ]);
                            } else {
                                // Just update document type if no new file
                                $existingDoc->update([
                                    'document_type_id' => $docData['document_type_id']
                                ]);
                            }

                            // Remove from existing documents so it won't be deleted
                            $existingDocuments->forget($docData['existing_document_id']);
                        } else {
                            // This is a new document
                            if (isset($docData['file']) && $docData['file']) {
                                $extension = $docData['file']->getClientOriginalExtension();
                                $documentTypeName = $docData['document_type_name'] ?? 'document';
                                $documentTypeName = strtolower(str_replace(' ', '_', $documentTypeName));

                                $filePath = $this->uploadFile(
                                    $docData['file'],
                                    "employees/{$employee->employee_code}/documents",
                                    "{$documentTypeName}.{$extension}"
                                );

                                EmployeeDocument::create([
                                    'employee_id' => $employee->id,
                                    'document_type_id' => $docData['document_type_id'],
                                    'file_path' => $filePath
                                ]);
                            }
                        }
                    }
                }

                // Delete any existing documents that were not in the request
                if ($existingDocuments->isNotEmpty()) {
                    $existingDocuments->each->delete();
                }
            } else {
                $existingDocuments = $employee->documents()->get();
                if ($existingDocuments->isNotEmpty()) {
                    //                    foreach ($existingDocuments as $doc) {
                    //                        $this->deleteFile($doc->file_path);
                    //                    }
                    $existingDocuments->each->delete();
                }
            }

            // Handle assigned devices
            $assignedDevices = $data['assigned_devices'] ?? [];
            unset($data['assigned_devices']);

            $syncData = [];
            foreach ($assignedDevices as $row) {
                if (empty($row['asset_id'])) continue;

                $syncData[(int) $row['asset_id']] = [
                    'assigned_date' => $row['assigned_date'] ?? now()->toDateString(),
                ];
            }

            $newIds = array_keys($syncData);
            $currentIds = $employee->assets()->pluck('assets.id')->all();

            $toDetach = array_values(array_diff($currentIds, $newIds));
            $toAttach = array_values(array_diff($newIds, $currentIds));

            // 1) Detach removed assets + mark them returned in history + clear assign_to
            if ($toDetach) {
                $employee->assets()->detach($toDetach);

                AssetAssignmentHistory::where('employee_id', $employee->id)
                    ->whereIn('asset_id', $toDetach)
                    ->whereNull('returned_at')
                    ->update(['returned_at' => now()]);

                Asset::whereIn('id', $toDetach)
                    ->where('assign_to', $employee->id)
                    ->update(['assign_to' => null]);
            }

            // 2) Attach new assets (enforce single assignee) + create history + set assign_to
            if ($toAttach) {

                $attachPayload = array_intersect_key($syncData, array_flip($toAttach));
                $employee->assets()->attach($attachPayload);
                //dd($attachPayload);
                foreach ($toAttach as $assetId) {
                    //                    dd('1', $assetId, data_get($syncData, $assetId . '.assigned_date'));
                    AssetAssignmentHistory::create([
                        'asset_id' => (int) $assetId,
                        'employee_id' => $employee->id,
                        'assigned_date' => data_get($syncData, $assetId . '.assigned_date'),
                    ]);
                }

                Asset::whereIn('id', $toAttach)->update(['assign_to' => $employee->id]);
            }
            //dd('ok');
            // 3) Update pivot assigned_date for assets that remain assigned
            if (!empty($syncData)) {
                $employee->assets()->syncWithoutDetaching($syncData);
            }

            // Update or create user account if official email is provided
            $this->syncUserForEmployee($employee, $data);
            $this->syncCrmAssignedManager($employee);

            return $employee;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $employee = Employee::findOrFail($id);

            // Delete associated user if exists
            if ($employee->user) {
                $employee->user->delete();
            }

            //            foreach (self::FILE_FIELDS as $field) {
            //                if ($employee->$field) {
            //                    $this->deleteFile($employee->$field);
            //                }
            //            }

            return $employee->delete();
        });
    }

    protected function createUserForEmployee(Employee $employee, array $data): User
    {
        $password = $data['official_email_password'];

        $userData = [
            'name' => $employee->name,
            'email' => $employee->official_email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ];

        $user = User::create($userData);
        $user->assignRole('Employee');

        $employee->user_id = $user->id;
        $employee->save();

        return $user;
    }

    protected function syncCrmAssignedManager($employee)
    {
        if (isset($employee->user) && $this->isRfqManager($employee->user)) {
            AssignedManager::updateOrCreate([
                'employee_id' => $employee->id,
            ], [
                'name' => $employee->name,
                'profileImage' => $employee->user->avatar ?? 'hrm/avatar/profileavatar.png',
                'role' => $employee->designation->title,
                'employment_status' => $employee->employmentStatus->name ?? null,
                'status' => $employee->status,
            ]);
        } else {
            AssignedManager::where('employee_id', $employee->id)->delete();
        }
    }

    public function isRfqManager($user): bool
    {
        if ($user) {
            return $user->hasAnyPermission([
                'rfq_manager.create',
                'rfq_manager.read',
                'rfq_manager.update',
                'rfq_manager.delete',
            ]);
        }

        return false;
    }

    protected function syncUserForEmployee(Employee $employee, array $data): ?User
    {
        if ($employee->user) {
            // Update existing user
            $updateData = [
                'name' => $employee->name,
                'email' => $employee->official_email,
            ];

            //            if (!empty($data['official_email_password'])) {
            //                $updateData['password'] = Hash::make($data['official_email_password']);
            //            }

            $employee->user->update($updateData);
            return $employee->user;
        }

        // Create new user if doesn't exist
        return $this->createUserForEmployee($employee, $data);
    }

    public function restore($id)
    {
        return Employee::withTrashed()->findOrFail($id)->restore();
    }

    public function forceDelete($id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);

        return DB::transaction(function () use ($employee) {
            // Delete associated user if exists
            if ($employee->user) {
                $employee->user->forceDelete();
            }

            //            foreach (self::FILE_FIELDS as $field) {
            //                if ($employee->$field) {
            //                    $this->deleteFile($employee->$field);
            //                }
            //            }

            return $employee->forceDelete();
        });
    }

    public function assignRoles($data)
    {
        $employee = Employee::where('id', $data['employee_id'])->first();

        if ($employee) {
            $employee->user?->syncRoles($data['role_ids']);
            $this->syncCrmAssignedManager($employee);
        }

        return true;
    }

    public function paginateAdmingHr(array $filters = []): Paginator
    {
        $query = Employee::with([
            'department',
            'designation',
            'branch',
            'employmentType',
            'employmentStatus',
            'reportingTo',
            'currentSalary.payslipType',
            'currentSalary.taxSlab',
        ]);
        $query = $this->applyAdminHrFilter($query, $filters);

        return $query->latest('id')->paginate($filters['per_page'] ?? 15);
    }

    public function applyAdminHrFilter($query, array $filters = [])
    {
        return $query
            // Restrict access based on role
            ->when(!auth()->user()->hasRole(['Hr', 'Super Admin']), function ($q) {
                // If only employee, show only their own record
                if (auth()->user()->onlyEmployee()) {
                    $q->where('employees.id', auth()->user()->employee->id ?? 0);
                }
                // If manager (but not HR), show self and direct reports
                elseif (auth()->user()->hasRole('Manager')) {
                    $empId = auth()->user()->employee->id ?? 0;
                    $q->where(function ($subQ) use ($empId) {
                        $subQ->where('employees.id', $empId)
                            ->orWhere('employees.reporting_to', $empId);
                    });
                }
            })

            // Search filter
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $search = $filters['q'];
                $query->whereAny([
                    'employees.name',
                    'employees.phone',
                    'employees.official_email',
                    'employees.personal_email',
                    'employees.employee_code',
                    'employees.cnic',
                ], 'ilike', "%{$search}%")
                    // salary search (exact-ish) if numeric
                    ->when(is_numeric($search), function ($q) use ($search) {
                        $q->orWhereHas('currentSalary', function ($qs) use ($search) {
                            $qs->where('amount', $search);
                        });
                    });
            })

            // Department filter
            ->when(isset($filters['department_id']), function ($query) use ($filters) {
                $query->where('employees.department_id', $filters['department_id']);
            })

            // Designation filter
            ->when(isset($filters['designation_id']), function ($query) use ($filters) {
                $query->where('employees.designation_id', $filters['designation_id']);
            })

            // Employment status filter
            ->when(isset($filters['employment_status_id']), function ($query) use ($filters) {
                $query->where('employees.employment_status_id', $filters['employment_status_id']);
            })

            // Employment type filter
            ->when(isset($filters['employment_type_id']), function ($query) use ($filters) {
                $query->where('employees.employment_type_id', $filters['employment_type_id']);
            })

            // Joining date filters
            ->when(!empty($filters['joining_start_date']), function ($query) use ($filters) {
                $query->whereDate('employees.joining_date', '>=', $filters['joining_start_date']);
            })
            ->when(!empty($filters['joining_end_date']), function ($query) use ($filters) {
                $query->whereDate('employees.joining_date', '<=', $filters['joining_end_date']);
            })

            // Salary range filters (current salary)
            ->when(isset($filters['salary_min']) || isset($filters['salary_max']), function ($query) use ($filters) {
                // Use whereHas to avoid breaking the base Employee query
                $query->whereHas('currentSalary', function ($q) use ($filters) {
                    if (isset($filters['salary_min']) && $filters['salary_min'] !== null && $filters['salary_min'] !== '') {
                        $q->where('amount', '>=', $filters['salary_min']);
                    }
                    if (isset($filters['salary_max']) && $filters['salary_max'] !== null && $filters['salary_max'] !== '') {
                        $q->where('amount', '<=', $filters['salary_max']);
                    }
                });
            })

            // Role assignment filter (exclude self)
            ->when(isset($filters['for_role_assignment']), function ($query) {
                $query->where('employees.id', '<>', auth()->user()->employee->id ?? 0);
            });
    }
}
