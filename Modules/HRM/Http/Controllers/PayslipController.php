<?php

namespace Modules\HRM\Http\Controllers;

use Modules\HRM\Models\Payroll;
use Modules\HRM\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class PayslipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payroll::with('employee');

        // Filter by month if provided
        if ($request->has('month') && $request->month) {
            $query->forMonth($request->month);
        }

        // Filter by employee if provided
        if ($request->has('employee_id') && $request->employee_id) {
            $query->forEmployee($request->employee_id);
        }

        // Search by employee name (support both 'search' and 'q' parameters)
        $searchTerm = $request->get('search') ?: $request->get('q');
        if ($searchTerm) {
            $query->whereHas('employee', function ($q) use ($searchTerm) {
                $q->where('name', 'ilike', '%' . $searchTerm . '%')
                  ->orWhere('employee_code', 'ilike', '%' . $searchTerm . '%')
                  ->orWhere('official_email', 'ilike', '%' . $searchTerm . '%')
                  ->orWhere('personal_email', 'ilike', '%' . $searchTerm . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sortBy', 'created_at');
        $orderBy = $request->get('orderBy', 'desc');
        
        // Validate sort fields to prevent SQL injection
        $allowedSortFields = ['created_at', 'basic_salary', 'total_allowances', 'total_deductions', 'total_loans', 'net_salary'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        // Validate order direction
        $orderBy = in_array(strtolower($orderBy), ['asc', 'desc']) ? strtolower($orderBy) : 'desc';
        
        $query->orderBy($sortBy, $orderBy);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $payrolls = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $payrolls->items(),
            'meta' => [
                'current_page' => $payrolls->currentPage(),
                'last_page' => $payrolls->lastPage(),
                'per_page' => $payrolls->perPage(),
                'total' => $payrolls->total(),
                'from' => $payrolls->firstItem(),
                'to' => $payrolls->lastItem(),
            ],
            'links' => [
                'first' => $payrolls->url(1),
                'last' => $payrolls->url($payrolls->lastPage()),
                'prev' => $payrolls->previousPageUrl(),
                'next' => $payrolls->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
                'basic_salary' => 'required|numeric|min:0',
                'total_allowances' => 'required|numeric|min:0',
                'total_deductions' => 'required|numeric|min:0',
                'total_loans' => 'required|numeric|min:0',
            ]);

            // Check if payroll already exists for this employee and month
            $existingPayroll = Payroll::where('employee_id', $validatedData['employee_id'])
                ->where('month', $validatedData['month'])
                ->first();

            if ($existingPayroll) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payroll already exists for this employee and month.'
                ], 422);
            }

            $payroll = Payroll::create($validatedData);
            $payroll->load('employee');

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll created successfully.',
                'data' => $payroll
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll): JsonResponse
    {
        $payroll->load('employee');

        return response()->json([
            'status' => 'success',
            'data' => $payroll
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payroll $payroll): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'employee_id' => 'sometimes|required|exists:employees,id',
                'month' => 'sometimes|required|string|regex:/^\d{4}-\d{2}$/',
                'basic_salary' => 'sometimes|required|numeric|min:0',
                'total_allowances' => 'sometimes|required|numeric|min:0',
                'total_deductions' => 'sometimes|required|numeric|min:0',
                'total_loans' => 'sometimes|required|numeric|min:0',
            ]);

            // Check if updating employee_id or month would create a duplicate
            if (isset($validatedData['employee_id']) || isset($validatedData['month'])) {
                $employeeId = $validatedData['employee_id'] ?? $payroll->employee_id;
                $month = $validatedData['month'] ?? $payroll->month;

                $existingPayroll = Payroll::where('employee_id', $employeeId)
                    ->where('month', $month)
                    ->where('id', '!=', $payroll->id)
                    ->first();

                if ($existingPayroll) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payroll already exists for this employee and month.'
                    ], 422);
                }
            }

            $payroll->update($validatedData);
            $payroll->load('employee');

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll updated successfully.',
                'data' => $payroll
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll): JsonResponse
    {
        try {
            $payroll->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate payroll for a specific employee and month.
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            ]);

            $employee = Employee::with(['currentSalary', 'allowances', 'deductions', 'loans'])->find($validatedData['employee_id']);

            if (!$employee) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee not found.'
                ], 404);
            }

            // Check if payroll already exists
            $existingPayroll = Payroll::where('employee_id', $validatedData['employee_id'])
                ->where('month', $validatedData['month'])
                ->first();

            if ($existingPayroll) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payroll already exists for this employee and month.'
                ], 422);
            }

            // Calculate payroll data
            $basicSalary = $employee->currentSalary ? $employee->currentSalary->amount : 0;
            $totalAllowances = $employee->allowances->sum('amount');
            $totalDeductions = $employee->deductions->sum('amount');
            $totalLoans = $employee->loans->sum('amount');
            $netSalary = $basicSalary + $totalAllowances - $totalDeductions - $totalLoans;

            // Create payroll record
            $payroll = Payroll::create([
                'employee_id' => $validatedData['employee_id'],
                'month' => $validatedData['month'],
                'basic_salary' => $basicSalary,
                'total_allowances' => $totalAllowances,
                'total_deductions' => $totalDeductions,
                'total_loans' => $totalLoans,
                'net_salary' => $netSalary,
            ]);

            $payroll->load('employee');

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll generated successfully.',
                'data' => $payroll
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate payroll: ' . $e->getMessage()
            ], 500);
        }
    }
}
