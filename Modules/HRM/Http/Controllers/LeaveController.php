<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HRM\Contracts\LeaveRepositoryInterface;
use Modules\HRM\Exports\LeavesExport;
use Modules\HRM\Exports\LeaveBalancesExport;
use Modules\HRM\Http\Requests\Leave\Hr\ApproveRejectRequest;
use Modules\HRM\Http\Requests\Leave\CreateUpdateRequest;
use Modules\HRM\Http\Resources\LeaveResource;
use Modules\HRM\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\HRM\Models\LeaveType;

class LeaveController extends Controller
{
    protected $repository;

    public function __construct(LeaveRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $leaves = $this->repository->paginate($request->all());
        return LeaveResource::collection($leaves);
    }

    public function store(CreateUpdateRequest $request)
    {
        $leave = $this->repository->create($request->validated());
        return new LeaveResource($leave->load(['employee', 'leaveType']));
    }

    public function show(Leave $leave)
    {
        return new LeaveResource($leave->load(['employee', 'leaveType']));
    }

    public function update(CreateUpdateRequest $request, Leave $leave)
    {
        $updated = $this->repository->update($leave->id, $request->validated());
        return new LeaveResource($updated);
    }

    public function destroy(Leave $leave)
    {
        $this->repository->delete($leave->id);
        return response()->noContent();
    }

    public function approveRejectByManager(Request $request, Leave $leave)
    {
        $leave = $this->repository->approveRejectByManager($leave->id, $request->all());
        return new LeaveResource($leave);
    }

    public function approveRejectByHr(ApproveRejectRequest $request, Leave $leave)
    {
        $leave = $this->repository->approveRejectByHr($leave->id, $request->except(['applied_start_date', 'applied_end_date']));
        return new LeaveResource($leave);
    }

    public function getUserLeaveBalance(Request $request, $employeeId)
    {
        $validator = Validator::make(['employee_id' => $employeeId], [
            'employee_id' => 'required|integer|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid employee ID',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leaveBalance = $this->repository->getUserLeaveBalance($employeeId);

            return response()->json([
                'success' => true,
                'data' => $leaveBalance,
                'message' => 'Leave balance retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leave balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function leaveBalances(Request $request)
    {
        $filters = $request->all();
        try {
            $balances = $this->repository->getAllLeaveBalances($filters);
            return response()->json([
                'success' => true,
                'data' => $balances,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leave balances',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function exportPdf(Request $request)
    {
        try {
            // Use leave repository to fetch all matching leaves for export
            $filters = $request->all();
            $filters['per_page'] = -1;

            $leaves = $this->repository->export($filters);

            $data = [
                'leaves' => $leaves,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => $leaves->count(),
            ];

            $filename = 'leaves_report';
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $filename .= '_' . $filters['from_date'] . '_to_' . $filters['to_date'];
            } elseif (!empty($filters['from_date'])) {
                $filename .= '_from_' . $filters['from_date'];
            } elseif (!empty($filters['to_date'])) {
                $filename .= '_until_' . $filters['to_date'];
            }
            if (!empty($filters['status'])) $filename .= '_' . $filters['status'];
            // append timestamp to filename for uniqueness
            $filename .= '_' . now()->format('Ymd_His');

            $pdf = Pdf::loadView('hrm::leaves.pdf-export', $data);
            $pdf->setPaper('A4', 'portrait');
            return $pdf->download($filename . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $filters = $request->all();
            $filters['per_page'] = -1;

            $leaves = $this->repository->export($filters);

            $data = [
                'leaves' => $leaves,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => $leaves->count(),
            ];

            $filename = 'leaves_report';
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $filename .= '_' . $filters['from_date'] . '_to_' . $filters['to_date'];
            } elseif (!empty($filters['from_date'])) {
                $filename .= '_from_' . $filters['from_date'];
            } elseif (!empty($filters['to_date'])) {
                $filename .= '_until_' . $filters['to_date'];
            }
            if (!empty($filters['status'])) $filename .= '_' . $filters['status'];
            $filename .= '_' . now()->format('Ymd_His');

            return Excel::download(new LeavesExport($data), $filename . '.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportBalancesExcel(Request $request)
    {
        try {
            $filters = $request->all();
            $filters['per_page'] = -1;

            $paginator = $this->repository->getAllLeaveBalances($filters);
            $items = $paginator->items();

            Log::info('exportBalancesExcel called', ['filters' => $filters, 'count' => count($items)]);

            // Get all leave types to form columns (respect sort_order)
            $leaveTypes = LeaveType::orderBy('sort_order')->orderBy('id')->get();
            $headings = array_merge(['Employee', 'Emp Code', 'Department'], $leaveTypes->pluck('name')->toArray());

            $rows = collect($items)->map(function ($item) use ($leaveTypes) {
                $employee = $item['employee'] ?? null;
                $empName = is_array($employee) ? ($employee['name'] ?? '-') : ($employee->name ?? '-');
                $empCode = is_array($employee) ? ($employee['employee_code'] ?? '-') : ($employee->employee_code ?? '-');
                $department = is_array($employee) ? ($employee['department']['name'] ?? '-') : ($employee->department->name ?? '-');

                // prepare per-leave-type columns
                $balancesMap = collect($item['balances'] ?? [])->mapWithKeys(function ($b) {
                    return [($b['leave_type_id'] ?? $b->leave_type_id) => $b];
                })->all();

                $row = [$empName, $empCode, $department];
                foreach ($leaveTypes as $lt) {
                    $b = $balancesMap[$lt->id] ?? null;
                    if ($b) {
                        $remaining = $b['remaining'] ?? ($b->remaining ?? 0);
                        $quota = $b['quota'] ?? ($b->quota ?? 0);
                        $row[] = "{$remaining}/{$quota}";
                    } else {
                        $row[] = "0/{$lt->quota}";
                    }
                }

                return $row;
            });

            $collection = new Collection($rows->values());

            $filename = 'leave_balances_' . now()->format('Ymd_His') . '.xlsx';
            return Excel::download(new LeaveBalancesExport($collection, $headings), $filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportBalancesPdf(Request $request)
    {
        try {
            $filters = $request->all();
            $filters['per_page'] = -1;

            $paginator = $this->repository->getAllLeaveBalances($filters);
            $items = $paginator->items();

            Log::info('exportBalancesPdf called', ['filters' => $filters, 'count' => count($items)]);

            // Respect sort_order for columns
            $leaveTypes = LeaveType::orderBy('sort_order')->orderBy('id')->get();
            $headings = array_merge(['Employee', 'Emp Code', 'Department'], $leaveTypes->pluck('name')->toArray());

            $rows = collect($items)->map(function ($item) use ($leaveTypes) {
                $employee = $item['employee'] ?? null;
                $empName = is_array($employee) ? ($employee['name'] ?? '-') : ($employee->name ?? '-');
                $empCode = is_array($employee) ? ($employee['employee_code'] ?? '-') : ($employee->employee_code ?? '-');
                $department = is_array($employee) ? ($employee['department']['name'] ?? '-') : ($employee->department->name ?? '-');

                $balancesMap = collect($item['balances'] ?? [])->mapWithKeys(function ($b) {
                    return [($b['leave_type_id'] ?? $b->leave_type_id) => $b];
                })->all();

                $row = [
                    'employee' => $empName,
                    'emp_code' => $empCode,
                    'department' => $department,
                ];

                foreach ($leaveTypes as $lt) {
                    $b = $balancesMap[$lt->id] ?? null;
                    if ($b) {
                        $remaining = $b['remaining'] ?? ($b->remaining ?? 0);
                        $quota = $b['quota'] ?? ($b->quota ?? 0);
                        $row[$lt->name] = "{$remaining}/{$quota}";
                    } else {
                        $row[$lt->name] = "0/{$lt->quota}";
                    }
                }

                return $row;
            })->all();

            $data = [
                'headings' => $headings,
                'rows' => $rows,
                'generated_at' => now()->format('d-m-Y H:i:s'),
            ];

            $filename = 'leave_balances_' . now()->format('Ymd_His') . '.pdf';
            $pdf = Pdf::loadView('hrm::leaves.balances-pdf', $data);
            // Use landscape to accommodate multiple leave-type columns
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
