<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HRM\Contracts\PayrollDeductionRepositoryInterface;
use Modules\HRM\Http\Resources\PayrollDeductionResource;
use Modules\HRM\Models\Employee;
use Modules\HRM\Models\EmployeeSalary;
use Modules\HRM\Models\TaxSlab;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\EmployeeResource;
use Modules\HRM\Http\Resources\SalaryResource;
use Modules\HRM\Http\Requests\SalaryRequest;
use Modules\HRM\Contracts\EmployeeRepositoryInterface;
use Modules\HRM\Contracts\SalaryRepositoryInterface;
use Modules\HRM\Contracts\EmployeeAllowanceRepositoryInterface;
use Modules\HRM\Contracts\EmployeeDeductionRepositoryInterface;
use Modules\HRM\Contracts\EmployeeLoanRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HRM\Exports\PayrollExport;
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Exports\SalaryGenerationExport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\HRM\Models\Payroll;
use Modules\HRM\Models\PayrollGeneration;
use Modules\HRM\Models\PayrollGenerationItem;
use Modules\HRM\Http\Requests\PayrollGenerationApprovalRequest;
use Modules\HRM\Http\Resources\EmployeeSalaryHistoryResource;
use Modules\HRM\Repositories\AttendanceRepository;

class PayrollController extends Controller
{
    protected $employeeRepository;
    protected $salaryRepository;
    protected $employeeAllowanceRepository;
    protected $employeeDeductionRepository;
    protected $employeeLoanRepository;
    protected $attendanceRepository;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepository,
        SalaryRepositoryInterface $salaryRepository,
        EmployeeAllowanceRepositoryInterface $employeeAllowanceRepository,
        EmployeeDeductionRepositoryInterface $employeeDeductionRepository,
        EmployeeLoanRepositoryInterface $employeeLoanRepository,
        AttendanceRepositoryInterface $attendanceRepository,
        PayrollDeductionRepositoryInterface $deductionRepository,
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->salaryRepository = $salaryRepository;
        $this->employeeAllowanceRepository = $employeeAllowanceRepository;
        $this->employeeDeductionRepository = $employeeDeductionRepository;
        $this->employeeLoanRepository = $employeeLoanRepository;
        $this->attendanceRepository = $attendanceRepository;
        $this->deductionRepository = $deductionRepository;
    }

    public function index(Request $request)
    {
        $employees = $this->employeeRepository->paginateAdmingHr($request->all());
        
        // Transform the data to include payroll information
        $payrollData = $employees->getCollection()->map(function ($employee) {
            $currentSalary = $employee->currentSalary;
            $taxAmount = $currentSalary && $currentSalary->is_tax_applicable ? (int) $currentSalary->tax_amount : 0;
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_code,
                'name' => $employee->name,
                'official_email' => $employee->official_email,
                'personal_email' => $employee->personal_email,
                'employee_code' => $employee->employee_code,
                'payroll_type' => $currentSalary && $currentSalary->payslipType ? $currentSalary->payslipType->name : 'Basic Salary',
                'salary' => $currentSalary ? $currentSalary->amount : 0,
                'net_salary' => $currentSalary ? $currentSalary->amount - $taxAmount : 0, // Will be calculated with allowances/deductions later
                'is_tax_applicable' => $currentSalary ? (bool) $currentSalary->is_tax_applicable : false,
                'tax_slab_id' => $currentSalary?->tax_slab_id,
                'tax_slab_name' => $currentSalary?->taxSlab?->name,
                'tax_amount' => $taxAmount,
                'employee' => new EmployeeResource($employee->load([
                    'department',
                    'designation',
                    'branch',
                    'employmentType',
                    'employmentStatus',
                    'currentSalary.payslipType',
                    'currentSalary.taxSlab'
                ]))
            ];
        });

        // Create a new paginator with the transformed data
        $payrollEmployees = new \Illuminate\Pagination\LengthAwarePaginator(
            $payrollData,
            $employees->total(),
            $employees->perPage(),
            $employees->currentPage(),
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $payrollEmployees->items(),
            'meta' => [
                'current_page' => $payrollEmployees->currentPage(),
                'from' => $payrollEmployees->firstItem(),
                'last_page' => $payrollEmployees->lastPage(),
                'per_page' => $payrollEmployees->perPage(),
                'to' => $payrollEmployees->lastItem(),
                'total' => $payrollEmployees->total(),
            ],
            'links' => [
                'first' => $payrollEmployees->url(1),
                'last' => $payrollEmployees->url($payrollEmployees->lastPage()),
                'prev' => $payrollEmployees->previousPageUrl(),
                'next' => $payrollEmployees->nextPageUrl(),
            ]
        ]);
    }

    public function show(Employee $employee)
    {
        $currentSalary = $employee->currentSalary;
        $payrollData = [
            'id' => $employee->id,
            'employee_id' => $employee->employee_code,
            'name' => $employee->name,
            'payroll_type' => $currentSalary && $currentSalary->payslipType ? $currentSalary->payslipType->name : 'Basic Salary',
            'salary' => $currentSalary ? $currentSalary->amount : 0,
            'net_salary' => $currentSalary ? $currentSalary->amount : 0,
            'is_tax_applicable' => $currentSalary ? (bool) $currentSalary->is_tax_applicable : false,
            'tax_slab_id' => $currentSalary?->tax_slab_id,
            'tax_slab_name' => $currentSalary?->taxSlab?->name,
            'tax_amount' => $currentSalary && $currentSalary->is_tax_applicable ? (int) $currentSalary->tax_amount : 0,
            'employee' => new EmployeeResource($employee->load([
                'department',
                'designation',
                'branch',
                'dependents',
                'assets',
                'jobCategory',
                'jobStage',
                'terminationType',
                'reportingTo',
                'employmentType',
                'employmentStatus',
                'currentSalary.payslipType',
                'currentSalary.taxSlab'
            ]))
        ];

        return response()->json([
            'status' => 'success',
            'data' => $payrollData
        ]);
    }

    // Salary CRUD Operations
    public function storeSalary(SalaryRequest $request)
    {
        $validatedData = $request->validated();

        $existingSalary = $this->salaryRepository->getCurrentSalary($validatedData['employee_id']);
        if ($existingSalary && $existingSalary->status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee already has an active salary. Please edit the existing salary or deactivate it first.'
            ], 422);
        }

        $salary = $this->salaryRepository->create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Salary created successfully',
            'data' => new SalaryResource($salary)
        ], 201);
    }

    public function updateSalary(SalaryRequest $request, EmployeeSalary $salary)
    {
        $validatedData = $request->validated();

        if (!empty($validatedData['is_tax_applicable'])) {
            if (empty($validatedData['tax_slab_id']) && isset($validatedData['amount'])) {
                $annualAmount = round((float)$validatedData['amount'] * 12, 2);
                $slab = TaxSlab::where('min_salary', '<=', $annualAmount)
                    ->where(function ($query) use ($annualAmount) {
                        $query->whereNull('max_salary')->orWhere('max_salary', '>=', $annualAmount);
                    })
                    ->orderBy('min_salary', 'desc')
                    ->first();

                $validatedData['tax_slab_id'] = $slab ? $slab->id : null;
            }
        } else {
            $validatedData['tax_slab_id'] = null;
        }
        $validatedData['tax_amount'] = $this->salaryRepository->calculateTaxAmount(
            $validatedData['amount'] ?? $salary->amount,
            $validatedData['tax_slab_id'] ?? $salary->tax_slab_id
        );
        $updatedSalary = $this->salaryRepository->update($salary->id, $validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Salary updated successfully',
            'data' => new SalaryResource($updatedSalary)
        ]);
    }

    public function deleteSalary(EmployeeSalary $salary)
    {
        $this->salaryRepository->delete($salary->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Salary deleted successfully'
        ]);
    }

    public function getEmployeeSalaries(Employee $employee)
    {
        $salaries = $this->salaryRepository->getByEmployee($employee->id);

        return response()->json([
            'status' => 'success',
            'data' => SalaryResource::collection($salaries)
        ]);
    }

    public function getCurrentSalary(Employee $employee)
    {
        $currentSalary = $this->salaryRepository->getCurrentSalary($employee->id);

        return response()->json([
            'status' => 'success',
            'data' => $currentSalary ? new SalaryResource($currentSalary) : null
        ]);
    }

    public function getEmployeeSalaryData(Employee $employee)
    {
        try {
            $currentSalary = $this->salaryRepository->getCurrentSalary($employee->id);
            $salaryHistory = $this->salaryRepository->getByEmployee($employee->id);
            $allowances = $this->employeeAllowanceRepository->getByEmployee($employee->id);
            $deductions = $this->employeeDeductionRepository->getByEmployee($employee->id);
            $loans = $this->employeeLoanRepository->getByEmployee($employee->id);

            $totalAllowances = collect($allowances)->sum('amount');
            $totalDeductions = collect($deductions)->sum('amount');
            $totalLoans = collect($loans)->sum('amount');
            $basicSalary = $currentSalary ? $currentSalary->amount : 0;
            $taxAmount = $currentSalary && $currentSalary->is_tax_applicable ? (int) $currentSalary->tax_amount : 0;
            $netSalary = $basicSalary - $taxAmount + $totalAllowances - $totalDeductions - $totalLoans;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'employee' => new EmployeeResource($employee->load([
                        'department',
                        'designation',
                        'branch',
                        'employmentType',
                        'employmentStatus'
                    ])),
                    'current_salary' => $currentSalary ? new SalaryResource($currentSalary) : null,
                    'salary_history' => SalaryResource::collection($salaryHistory),
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'loans' => $loans,
                    'totals' => [
                        'basic_salary' => $basicSalary,
                        'tax_amount' => $taxAmount,
                        'total_allowances' => $totalAllowances,
                        'total_deductions' => $totalDeductions,
                        'total_loans' => $totalLoans,
                        'net_salary' => $netSalary
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch employee salary data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSalaryHistory(Employee $employee)
    {
        $history = $this->salaryRepository->getHistoryByEmployee($employee->id);

        return response()->json([
            'status' => 'success',
            'data' => EmployeeSalaryHistoryResource::collection($history)
        ]);
    }

    public function payslip(Request $request)
    {
        $employees = $this->employeeRepository->paginate($request->all());
        
        // Transform the data to include payslip information with totals
        $payslipData = $employees->getCollection()->map(function ($employee) {
            $currentSalary = $employee->currentSalary;
            
            // Get employee allowances
            $allowances = $this->employeeAllowanceRepository->getByEmployee($employee->id);
            
            // Get employee deductions
            $deductions = $this->employeeDeductionRepository->getByEmployee($employee->id);
            
            // Get employee loans
            $loans = $this->employeeLoanRepository->getByEmployee($employee->id);
            
            // Calculate totals
            $totalAllowances = collect($allowances)->sum('amount');
            $totalDeductions = collect($deductions)->sum('amount');
            $totalLoans = collect($loans)->sum('amount');
            $basicSalary = $currentSalary ? $currentSalary->amount : 0;
            $netSalary = $basicSalary + $totalAllowances - $totalDeductions - $totalLoans;
            
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_code,
                'name' => $employee->name,
                'payroll_type' => $currentSalary && $currentSalary->payslipType ? $currentSalary->payslipType->name : 'Basic Salary',
                'salary' => $basicSalary,
                'total_allowances' => $totalAllowances,
                'total_deductions' => $totalDeductions,
                'total_loans' => $totalLoans,
                'net_salary' => $netSalary,
                'employee' => new EmployeeResource($employee->load([
                    'department',
                    'designation',
                    'branch',
                    'employmentType',
                    'employmentStatus',
                    'currentSalary.payslipType'
                ]))
            ];
        });

        // Create a new paginator with the transformed data
        $payslipEmployees = new \Illuminate\Pagination\LengthAwarePaginator(
            $payslipData,
            $employees->total(),
            $employees->perPage(),
            $employees->currentPage(),
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $payslipEmployees->items(),
            'meta' => [
                'current_page' => $payslipEmployees->currentPage(),
                'from' => $payslipEmployees->firstItem(),
                'last_page' => $payslipEmployees->lastPage(),
                'per_page' => $payslipEmployees->perPage(),
                'to' => $payslipEmployees->lastItem(),
                'total' => $payslipEmployees->total(),
            ],
            'links' => [
                'first' => $payslipEmployees->url(1),
                'last' => $payslipEmployees->url($payslipEmployees->lastPage()),
                'prev' => $payslipEmployees->previousPageUrl(),
                'next' => $payslipEmployees->nextPageUrl(),
            ]
        ]);
    }

    public function downloadPayslip(Request $request, Employee $employee)
    {
        try {
            $validatedData = $request->validate([
                'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
                'format' => 'required|string|in:pdf,csv'
            ]);

            $month = $validatedData['month'];
            $format = $validatedData['format'];

            // Get current salary
            $currentSalary = $this->salaryRepository->getCurrentSalary($employee->id);
            
            // Get employee allowances for the month
            $allowances = $this->employeeAllowanceRepository->getByEmployee($employee->id);
            
            // Get employee deductions for the month
            $deductions = $this->employeeDeductionRepository->getByEmployee($employee->id);
            
            // Get employee loans for the month
            $loans = $this->employeeLoanRepository->getByEmployee($employee->id);
            
            // Calculate totals
            $totalAllowances = collect($allowances)->sum('amount');
            $totalDeductions = collect($deductions)->sum('amount');
            $totalLoans = collect($loans)->sum('amount');
            $basicSalary = $currentSalary ? $currentSalary->amount : 0;
            $netSalary = $basicSalary + $totalAllowances - $totalDeductions - $totalLoans;

            // Prepare payslip data
            $payslipData = [
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_code,
                    'name' => $employee->name,
                    'department' => $employee->department->name ?? 'N/A',
                    'designation' => $employee->designation->title ?? 'N/A',
                    'date_of_joining' => $employee->date_of_joining,
                ],
                'month' => $month,
                'salary_details' => [
                    'basic_salary' => $basicSalary,
                    'total_allowances' => $totalAllowances,
                    'total_deductions' => $totalDeductions,
                    'total_loans' => $totalLoans,
                    'net_salary' => $netSalary,
                ],
                'allowances' => $allowances,
                'deductions' => $deductions,
                'loans' => $loans,
            ];

            if ($format === 'pdf') {
                return $this->generatePdfPayslip($payslipData);
            } else {
                return $this->generateCsvPayslip($payslipData);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate payslip: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generatePdfPayslip($payslipData)
    {
        // For now, return a simple text response
        // In a real implementation, you would use a PDF library like DomPDF or TCPDF
        $content = "PAYSLIP\n";
        $content .= "========\n\n";
        $content .= "Employee ID: " . $payslipData['employee']['employee_id'] . "\n";
        $content .= "Name: " . $payslipData['employee']['name'] . "\n";
        $content .= "Department: " . $payslipData['employee']['department'] . "\n";
        $content .= "Designation: " . $payslipData['employee']['designation'] . "\n";
        $content .= "Month: " . $payslipData['month'] . "\n\n";
        $content .= "SALARY BREAKDOWN\n";
        $content .= "================\n";
        $content .= "Basic Salary: Rs " . number_format($payslipData['salary_details']['basic_salary'], 2) . "\n";
        $content .= "Total Allowances: Rs " . number_format($payslipData['salary_details']['total_allowances'], 2) . "\n";
        $content .= "Total Deductions: Rs " . number_format($payslipData['salary_details']['total_deductions'], 2) . "\n";
        $content .= "Total Loans: Rs " . number_format($payslipData['salary_details']['total_loans'], 2) . "\n";
        $content .= "Net Salary: Rs " . number_format($payslipData['salary_details']['net_salary'], 2) . "\n";

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $payslipData['employee']['employee_id'] . '_payslip_' . $payslipData['month'] . '.pdf"')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    private function generateCsvPayslip($payslipData)
    {
        $csvContent = "Employee ID,Name,Department,Designation,Month,Basic Salary,Total Allowances,Total Deductions,Total Loans,Net Salary\n";
        $csvContent .= $payslipData['employee']['employee_id'] . ",";
        $csvContent .= $payslipData['employee']['name'] . ",";
        $csvContent .= $payslipData['employee']['department'] . ",";
        $csvContent .= $payslipData['employee']['designation'] . ",";
        $csvContent .= $payslipData['month'] . ",";
        $csvContent .= $payslipData['salary_details']['basic_salary'] . ",";
        $csvContent .= $payslipData['salary_details']['total_allowances'] . ",";
        $csvContent .= $payslipData['salary_details']['total_deductions'] . ",";
        $csvContent .= $payslipData['salary_details']['total_loans'] . ",";
        $csvContent .= $payslipData['salary_details']['net_salary'] . "\n";

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $payslipData['employee']['employee_id'] . '_payslip_' . $payslipData['month'] . '.csv"')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    public function downloadPayslipGet(Request $request, Employee $employee)
    {
        try {
            $validatedData = $request->validate([
                'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
                'format' => 'required|string|in:pdf,csv',
                'token' => 'required|string'
            ]);

            $month = $validatedData['month'];
            $format = $validatedData['format'];

            // Get current salary
            $currentSalary = $this->salaryRepository->getCurrentSalary($employee->id);
            
            // Get employee allowances for the month
            $allowances = $this->employeeAllowanceRepository->getByEmployee($employee->id);
            
            // Get employee deductions for the month
            $deductions = $this->employeeDeductionRepository->getByEmployee($employee->id);
            
            // Get employee loans for the month
            $loans = $this->employeeLoanRepository->getByEmployee($employee->id);
            
            // Calculate totals
            $totalAllowances = collect($allowances)->sum('amount');
            $totalDeductions = collect($deductions)->sum('amount');
            $totalLoans = collect($loans)->sum('amount');
            $basicSalary = $currentSalary ? $currentSalary->amount : 0;
            $netSalary = $basicSalary + $totalAllowances - $totalDeductions - $totalLoans;

            // Prepare payslip data
            $payslipData = [
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_code,
                    'name' => $employee->name,
                    'department' => $employee->department->name ?? 'N/A',
                    'designation' => $employee->designation->title ?? 'N/A',
                    'date_of_joining' => $employee->date_of_joining,
                ],
                'month' => $validatedData['month'],
                'salary_details' => [
                    'basic_salary' => $basicSalary,
                    'total_allowances' => $totalAllowances,
                    'total_deductions' => $totalDeductions,
                    'total_loans' => $totalLoans,
                    'net_salary' => $netSalary,
                ],
                'allowances' => $allowances,
                'deductions' => $deductions,
                'loans' => $loans,
            ];

            if ($format === 'pdf') {
                return $this->generatePdfPayslip($payslipData);
            } else {
                return $this->generateCsvPayslip($payslipData);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate payslip: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $filters = $request->all();
            $filters['per_page'] = -1;

            $employees = $this->employeeRepository->paginateAdmingHr($filters);

            $payrollRows = collect($employees->items())->map(function ($employee) {
                $currentSalary = $employee->currentSalary;
                $taxAmount = $currentSalary && $currentSalary->is_tax_applicable ? (int) $currentSalary->tax_amount : 0;

                return [
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'email' => $employee->official_email ?: $employee->personal_email,
                    'payroll_type' => $currentSalary && $currentSalary->payslipType ? $currentSalary->payslipType->name : 'Basic Salary',
                    'salary' => $currentSalary?->amount ?? 0,
                    'net_salary' => $currentSalary ? $currentSalary->amount - $taxAmount : 0,
                    'is_tax_applicable' => $currentSalary ? (bool) $currentSalary->is_tax_applicable : false,
                    'tax_slab_name' => $currentSalary?->taxSlab?->name,
                    'tax_amount' => $taxAmount,
                ];
            });

            $data = [
                'rows' => $payrollRows,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => $employees->total(),
            ];

            $filename = 'payroll_report_' . now()->format('Ymd_His') . '.pdf';

            $pdf = Pdf::loadView('hrm::payroll.pdf-export', $data);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $filters = $request->all();
            $filters['per_page'] = -1;

            $employees = $this->employeeRepository->paginateAdmingHr($filters);

            $payrollRows = collect($employees->items())->map(function ($employee) {
                $currentSalary = $employee->currentSalary;

                return [
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'department' => $employee->department?->name,
                    'designation' => $employee->designation?->title,
                    'employment_type' => $employee->employmentType?->name,
                    'employment_status' => $employee->employmentStatus?->name,
                    'joining_date' => $employee->joining_date,
                    'phone' => $employee->phone,
                    'official_email' => $employee->official_email,
                    'cnic' => $employee->cnic,
                    'salary' => $currentSalary?->amount ?? 0,
                    'payroll_type' => $currentSalary && $currentSalary->payslipType ? $currentSalary->payslipType->name : 'Basic Salary',
                ];
            });

            $data = [
                'rows' => $payrollRows,
                'filters' => $filters,
                'generated_at' => now()->format('d-m-Y H:i:s'),
                'total_records' => $employees->total(),
            ];

            $filename = 'payroll_report_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new PayrollExport($data), $filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generatePayslip(Request $request, Employee $employee)
    {
        // This method will be implemented when payslip functionality is added
        // For now, just return a placeholder response
        return response()->json([
            'status' => 'success',
            'message' => 'Payslip generation functionality will be implemented when payslip table is added',
            'data' => [
                'employee_id' => $employee->employee_code,
                'name' => $employee->name
            ]
        ]);
    }

    private function buildSalaryGenerationRows(string $month, ?int $departmentId = null, ?string $q = null): array
    {
        $att = $this->attendanceRepository->getMonthlyEmployeeSummary([
            'month' => $month,
            'department_id' => $departmentId,
            'q' => $q,
        ]);

        $rows = $att['rows'] ?? [];

        // Collect employee codes from attendance rows
        $codes = collect($rows)->pluck('employee_code')->filter()->values()->all();

        // Load payroll-related sums in bulk
        $employees = Employee::query()
            ->whereIn('employee_code', $codes)
            ->with(['currentSalary', 'allowances'])
            ->get()
            ->keyBy('employee_code');

        $out = [];
        foreach ($rows as $r) {
            $code = $r['employee_code'] ?? null;
            $emp = $code ? ($employees[$code] ?? null) : null;

            $salary = (float) ($emp?->currentSalary?->amount ?? 0);
            $allowances = (float) collect($emp?->allowances ?? [])->sum('amount');

            $gross = $salary + $allowances;

            $out[] = array_merge($r, [
                'salary' => $salary,
                'allowances' => $allowances,
                'gross_salary' => $gross,
            ]);
        }

        return $out;
    }

private function buildSalaryGenerationRowsFromGenerationItems(string $month, ?int $departmentId = null, ?string $q = null, ?int $employmentStatusId = null): array
{
    $att = $this->attendanceRepository->getMonthlyEmployeeSummary([
        'month' => $month,
        'department_id' => $departmentId,
        'q' => $q,
    ]);

    $attendanceMap = collect($att['rows'] ?? [])->keyBy('employee_code');

    $items = PayrollGenerationItem::query()
        ->where('month', $month)
        ->whereHas('employee', function ($empQ) use ($departmentId, $q, $employmentStatusId) {
            if (!empty($departmentId)) {
                $empQ->where('department_id', $departmentId);
            }
            if (!empty($employmentStatusId)) {
                $empQ->where('employment_status_id', $employmentStatusId);
            }
            if (!empty($q)) {
                $term = trim($q);
                $empQ->where(function ($w) use ($term) {
                    $w->where('name', 'ilike', "%{$term}%")
                        ->orWhere('employee_code', 'ilike', "%{$term}%")
                        ->orWhere('official_email', 'ilike', "%{$term}%")
                        ->orWhere('personal_email', 'ilike', "%{$term}%")
                        ->orWhere('phone', 'ilike', "%{$term}%");
                });
            }
        })
        ->with(['employee.department', 'employee.designation', 'employee.currentSalary'])
        ->get();

    $out = [];
    foreach ($items as $it) {
        $emp = $it->employee;
        if (!$emp) {
            continue;
        }

        $code = $emp->employee_code;
        $r = $attendanceMap[$code] ?? [];

        $basic = (float) ($it->employee?->currentSalary?->amount ?? 0);
        $allow = (float) ($it->total_allowances ?? 0);
        $tax = (float) ($it->tax_amount ?? 0);
        $net = (float) ($it->net_salary ?? ($basic + $allow - $tax));

        $totalLeaves = 0;
//        dd(count($r));
        if(!empty($r)){
            $leaves = (float) ($r['leave'] ?? 0);
            $halfLeaves = (float) ($r['half_day'] ?? 0) * 0.5;
            $shortLeaves = (float) ($r['short_leave'] ?? 0) * 0.25;
            $totalLeaves = $leaves + $halfLeaves + $shortLeaves;
        }


        $out[] = array_merge($r, [
            'id' => $emp->id,
            'employee_code' => $code,
            'name' => $emp->name,
            'department' => $emp->department?->name,
            'designation' => $emp->designation?->title,

            // payroll columns
            'employee_id' => $emp->id,
            'month' => $month,
            'salary' => $basic,
            'allowances' => $allow,
            'attendance_deduction' => (float) ($it->attendance_deduction ?? 0),
            'gross_salary' => $net,
            'tax_amount' => $tax,

            // attendance columns (fallback for attendance-exempt/no-attendance rows)
            'total_working_days' => (int) ($r['total_working_days'] ?? 0),
            'present' => (int) ($r['present'] ?? 0),
            'wfh' => (int) ($r['wfh'] ?? 0),
            'late_arrivals' => (int) ($r['late_arrivals'] ?? 0),
            'total_present' => (int) ($r['total_present'] ?? 0),
            'leave' => (float) (($totalLeaves) ?? 0),
            'absent' => (int) ($r['absent'] ?? 0),
            'not_marked' => (int) ($r['not_marked'] ?? 0),
            'allocated_minutes' => (int) ($r['allocated_minutes'] ?? 0),
            'worked_minutes' => (int) ($r['worked_minutes'] ?? 0),

            // approvals/status
            'hr_approved' => (bool) ($it->hr_approved ?? false),
            'hr_approved_at' => $it->hr_approved_at,
            'ceo_approved' => (bool) ($it->ceo_approved ?? false),
            'ceo_approved_at' => $it->ceo_approved_at,
            'status' => (string) ($it->status ?? 'generated'),
            'created_at' => $it->created_at,
            'payroll_generated' => true,
        ]);
    }

    return $out;
}

public function salaryGeneration(Request $request)
{
    try {
        $month = $request->get('month', now()->format('Y-m'));
        $departmentId = $request->get('department_id');
        $q = $request->get('q');
        $employmentStatusId = $request->get('employment_status_id');

        $rows = $this->buildSalaryGenerationRowsFromGenerationItems(
            $month,
            $departmentId ? (int)$departmentId : null,
            $q,
            $employmentStatusId ? (int)$employmentStatusId : null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'filters' => [
                    'month' => $month,
                    'department_id' => $departmentId,
                    'q' => $q,
                ],
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch salary generation report: ' . $e->getMessage(),
        ], 500);
    }
}

    public function generateSalaryGenerationPayrolls(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'scope' => 'required|in:company,department,employees',
            'department_id' => 'nullable|required_if:scope,department|exists:departments,id',
            'employee_ids' => 'nullable|required_if:scope,employees|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'overwrite' => 'sometimes|boolean',
        ]);

        $month = $validated['month'];
        $scope = $validated['scope'];
        $overwrite = (bool)($validated['overwrite'] ?? false);

        $employeeQuery = Employee::query()->with(['currentSalary', 'allowances', 'exemption']);
        if ($scope === 'department') {
            $employeeQuery->where('department_id', (int)$validated['department_id']);
        } elseif ($scope === 'employees') {
            $employeeQuery->whereIn('id', $validated['employee_ids']);
        }
        $employeeQuery->where('employment_status_id', 1);
        $employees = $employeeQuery->get();

        // Get attendance summary for all employees in this month
        $attendanceSummary = app(AttendanceRepository::class)
            ->getMonthlyEmployeeSummary(['month' => $month]);
        $attendanceMap = collect($attendanceSummary['rows'] ?? [])->keyBy('employee_code');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $generation = null;

        DB::transaction(function () use ($month, $scope, $validated, $employees, $overwrite, &$created, &$updated, &$skipped, &$generation, $attendanceMap) {
            $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
            $daysInMonth = $monthStart->daysInMonth;

            $generation = PayrollGeneration::create([
                'month' => $month,
                'scope' => $scope,
                'department_id' => $scope === 'department' ? (int)$validated['department_id'] : null,
                'generated_by' => auth()->id(),
                'generated_at' => now(),
            ]);

            foreach ($employees as $employee) {
                $basicSalary = $employee->currentSalary ? (float)$employee->currentSalary->amount : 0;
                $totalAllowances = (float)$employee->allowances->sum('amount');
                $grossSalary = $basicSalary + $totalAllowances;

                // Calculate per-day salary
                $perDaySalary = $daysInMonth > 0 ? ($basicSalary / $daysInMonth) : 0;

                // Attendance deduction logic
                $att = $attendanceMap[$employee->employee_code] ?? null;
                $deductionDays = 0.0;
                if ($att) {
                    // Uninformed/Absent (without approval): 1-day salary deduction per day
                    $deductionDays += (float)($att['absent'] ?? 0);
                }

                // Attendance-exempt employees can have no attendance rows.
                // If they joined in generation month, deduct only pre-joining days.
                $joiningDate = $employee->date_of_joining ? Carbon::parse($employee->date_of_joining)->startOfDay() : null;
                $joinedInGenerationMonth = $joiningDate
                    && $joiningDate->year === $monthStart->year
                    && $joiningDate->month === $monthStart->month;
                $isAttendanceExempt = (bool) ($employee->exemption?->attendance_exemption ?? false);

                if ($isAttendanceExempt && $joinedInGenerationMonth) {
                    $deductionDays = max(0, $joiningDate->day);
                }

                $deductionAmount = round($perDaySalary * $deductionDays, 2);

                $taxableSalary = max($basicSalary, 0);
                $taxAmount = 0.0;
                if ($employee->currentSalary && $employee->currentSalary->is_tax_applicable) {
                    $taxAmount = $this->salaryRepository->calculateTaxAmount($taxableSalary);
                }

                $netSalary = $grossSalary - $deductionAmount - $taxAmount;
 
                $payload = [
                    'payroll_generation_id' => $generation->id,
                    'employee_id' => $employee->id,
                    'month' => $month,
                    'basic_salary' => $basicSalary,
                    'total_allowances' => $totalAllowances,
                    'tax_amount' => $taxAmount,
                    'attendance_deduction' => $deductionAmount,
                    'total_deductions' => 0, // keep other deductions separate if needed
                    'net_salary' => $netSalary,
                    'status' => 'generated',
                ];

                $existing = PayrollGenerationItem::where('employee_id', $employee->id)->where('month', $month)->first();

                if ($existing) {
                    if ($overwrite) {
                        unset($payload['status']);
                        $existing->update($payload);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                    continue;
                }

                PayrollGenerationItem::create($payload);
                $created++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Payroll generated successfully',
            'meta' => [
                'generation_id' => $generation?->id,
                'month' => $month,
                'scope' => $scope,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
            ],
        ], 201);
    }

public function exportSalaryGenerationPdf(Request $request)
{
    try {
        $month = $request->get('month', now()->format('Y-m'));
        $departmentId = $request->get('department_id');
        $q = $request->get('q');
        $employmentStatusId = $request->get('employment_status_id');

        $rows = $this->buildSalaryGenerationRowsFromGenerationItems(
            $month,
            $departmentId ? (int)$departmentId : null,
            $q,
            $employmentStatusId ? (int)$employmentStatusId : null,
        );

        $data = [
            'month' => $month,
            'rows' => $rows,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => count($rows),
        ];

        $pdf = Pdf::loadView('hrm::payroll.salary-generation-pdf', $data);
        $pdf->setPaper('A3', 'landscape');

        return $pdf->download('salary_generation_' . $month . '.pdf');
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to export PDF: ' . $e->getMessage(),
        ], 500);
    }
}

public function exportSalaryGenerationExcel(Request $request)
{
    try {
        $month = $request->get('month', now()->format('Y-m'));
        $departmentId = $request->get('department_id');
        $q = $request->get('q');
        $employmentStatusId = $request->get('employment_status_id');

        $rows = $this->buildSalaryGenerationRowsFromGenerationItems(
            $month,
            $departmentId ? (int)$departmentId : null,
            $q,
            $employmentStatusId ? (int)$employmentStatusId : null,
        );

        $data = [
            'month' => $month,
            'rows' => $rows,
            'generated_at' => now()->format('d-m-Y H:i:s'),
            'total_records' => count($rows),
        ];

        return Excel::download(
            new SalaryGenerationExport($data),
            'salary_generation_' . $month . '.xlsx'
        );
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to export Excel: ' . $e->getMessage(),
        ], 500);
    }
}

private function userHasAnyRoleInsensitive(array $roleNames): bool
{
    $user = auth()->user();
    if (!$user || !method_exists($user, 'hasRole')) return false;

    // Try exact names first
    if ($user->hasRole($roleNames)) return true;

    // Also try common case variants
    $variants = [];
    foreach ($roleNames as $r) {
        $variants[] = strtolower($r);
        $variants[] = strtoupper($r);
        $variants[] = ucfirst(strtolower($r));
    }

    $variants = array_values(array_unique(array_merge($roleNames, $variants)));

    return $user->hasRole($variants);
}

public function approveSalaryGeneration(PayrollGenerationApprovalRequest $request)
{
    $validated = $request->validated();

    $month = $validated['month'];
    $scope = $validated['scope']; // hr|ceo
    $employeeIds = $validated['employee_ids'];
    $approved = (bool) $validated['approved'];

    if ($scope === 'hr') {
        if (!$this->userHasAnyRoleInsensitive(['HR', 'Hr', 'hR', 'hr'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized (HR role required).'], 403);
        }
    }

    if ($scope === 'ceo') {
        if (!$this->userHasAnyRoleInsensitive(['CEO', 'Ceo', 'ceO', 'ceo'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized (CEO role required).'], 403);
        }
    }

    $updated = 0;
    DB::transaction(function () use ($month, $scope, $employeeIds, $approved, &$updated) {
        $items = PayrollGenerationItem::query()
            ->where('month', $month)
            ->whereIn('employee_id', $employeeIds)
            ->lockForUpdate()
            ->get();

        foreach ($items as $it) {
            if ($scope === 'hr') {
                $it->hr_approved = $approved;
                $it->hr_approved_at = $approved ? now() : null;
                $it->hr_approved_by = $approved ? auth()->id() : null;

                // status transitions
                if ($approved) {
                    $it->status = $it->ceo_approved ? 'approved' : 'hr_approved';
                } else {
                    $it->status = 'generated';
                    $it->ceo_approved = false;
                    $it->ceo_approved_at = null;
                    $it->ceo_approved_by = null;
                }
            }

            if ($scope === 'ceo') {
                // CEO can only approve after HR approve
                if (!$it->hr_approved) continue;

                $it->ceo_approved = $approved;
                $it->ceo_approved_at = $approved ? now() : null;
                $it->ceo_approved_by = $approved ? auth()->id() : null;

                $it->status = $approved ? 'approved' : 'hr_approved';
            }

            $it->save();
            $updated++;
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Updated successfully',
        'meta' => [
            'month' => $month,
            'scope' => $scope,
            'updated' => $updated,
        ],
    ]);
}

// In buildSalaryGenerationRowsFromGenerationItems(), add these fields to the merge:
// 'hr_approved_at' => $it->hr_approved_at,
// 'ceo_approved_at' => $it->ceo_approved_at,


    public function deductions(Request $request)
    {
        $deductions = $this->deductionRepository->paginate($request->all());
        return PayrollDeductionResource::collection($deductions);
    }

    public function updateDeductionStatus($id)
    {
        $deduction = $this->deductionRepository->updateStatus($id);
        return new PayrollDeductionResource($deduction);
    }
}
