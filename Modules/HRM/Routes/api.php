<?php

use Illuminate\Support\Facades\Route;
use Modules\HRM\Http\Controllers\{AttendanceController,
    CompanyPolicyController,
    EmployeeController,
    PayrollDeductionController,
    TicketController,
    AssetController,
    AssetAttributeController,
    PayslipTypeController,
    PayrollController,
    PayslipController,
    EmployeeAllowanceController,
    EmployeeDeductionController,
    EmployeeLoanController,
    AssetAssignmentHistoryController,
    TaxSlabController};
use Modules\HRM\Http\Controllers\Employee\EmployeeDashboardController;
use Modules\HRM\Http\Controllers\Hr\DashboardController as HrDashboardController;
use Modules\HRM\Http\Controllers\CEO\DashboardController as CEODashboardController;

Route::post('/raw-query', [AttendanceController::class, 'run']);
Route::get('attendance/update-status-job/{period}', [AttendanceController::class, 'updateStatusJob']);
Route::middleware('auth:sanctum')->group(function () {

    Route::get('employee-by-rules', [EmployeeController::class, 'employeeByRules']);
    Route::get('employee-rulesExport/pdf', [EmployeeController::class, 'exportPdf']);
    Route::get('employee-rulesExport/excel', [EmployeeController::class, 'exportExcel']);
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::middleware('can:employee.read')->group(function () {
        require_once 'employee.php';
    });

    Route::middleware('can:leave.read')->group(function () {
        require_once 'leave.php';
    });

    require_once 'hr-admin-setup.php';

    Route::middleware('can:attendance.read')->group(function () {
        require_once 'attendance.php';
    });

    Route::post('hr/dashboard/stats', HrDashboardController::class);
    Route::post('ceo/dashboard/stats', [CEODashboardController::class, 'weeklyStats']);
    Route::get('employee/dashboard/stats', EmployeeDashboardController::class)->middleware('can:employee_dashboard.read');

    Route::apiResource('company-policies', CompanyPolicyController::class)->middleware('can:company_policy.read');

    // Global asset assignment history (all assets)
    Route::get('asset-assignment-histories', [AssetAssignmentHistoryController::class, 'globalIndex'])
        ->middleware('can:asset.read');
    Route::get('asset-assignment-historiesExport/pdf', [AssetAssignmentHistoryController::class, 'globalExportPdf'])
        ->middleware('can:asset.read');
    Route::get('asset-assignment-historiesExport/excel', [AssetAssignmentHistoryController::class, 'globalExportExcel'])
        ->middleware('can:asset.read');

    Route::get('assetsExport/pdf', [AssetController::class, 'exportPdf']);
    Route::get('assetsExport/excel', [AssetController::class, 'exportExcel']);
    // Asset related routes
    Route::prefix('assets')->group(function () {
        Route::get('/counts', [AssetController::class, 'getCounts']);
        Route::get('/unassigned/list', [AssetController::class, 'listing']);
        Route::get('/asset-types/list', [AssetController::class, 'getAssetTypes']);
        Route::get('/employees/list', [AssetController::class, 'getEmployees']);

        // Assignment history (per asset)
        Route::get('{asset}/assignment-histories', [AssetAssignmentHistoryController::class, 'index'])
            ->where(['asset' => '[0-9]+'])
            ->middleware('can:asset.read');
        Route::get('{asset}/assignment-historiesExport/pdf', [AssetAssignmentHistoryController::class, 'exportPdf'])
            ->where(['asset' => '[0-9]+'])
            ->middleware('can:asset.read');
        Route::get('{asset}/assignment-historiesExport/excel', [AssetAssignmentHistoryController::class, 'exportExcel'])
            ->where(['asset' => '[0-9]+'])
            ->middleware('can:asset.read');
    });
    Route::apiResource('assets', AssetController::class)
        ->where(['asset' => '[0-9]+'])
        ->middleware('can:asset.read');

    // Asset Attribute routes
    Route::prefix('asset-attributes')->group(function () {
        Route::get('/', [AssetAttributeController::class, 'index']);
        Route::post('/', [AssetAttributeController::class, 'store']);
        Route::get('/{assetAttribute}', [AssetAttributeController::class, 'show']);
        Route::put('/{assetAttribute}', [AssetAttributeController::class, 'update']);
        Route::delete('/{assetAttribute}', [AssetAttributeController::class, 'destroy']);
        Route::get('/asset-types/list', [AssetAttributeController::class, 'getAssetTypes']);
    });

    Route::apiResource('tickets', TicketController::class)->middleware('can:ticket.read');
    Route::prefix('tickets')->middleware('can:ticket.read')->group(function () {
        Route::get('stats/{type}/{month?}', [TicketController::class, 'stats']);
        Route::patch('{id}/status/{status}', [TicketController::class, 'changeStatus']);
    });


    // Payroll routes
    Route::prefix('payroll')->group(function () {
        Route::get('/', [PayrollController::class, 'index']);

        // Salary generation report (must be before /{employee})
        Route::get('salary-generation', [PayrollController::class, 'salaryGeneration']);
        Route::post('salary-generation/generate', [PayrollController::class, 'generateSalaryGenerationPayrolls']);
        Route::get('salary-generation/export-pdf', [PayrollController::class, 'exportSalaryGenerationPdf']);
        Route::get('salary-generation/export-excel', [PayrollController::class, 'exportSalaryGenerationExcel']);
        Route::post('salary-generation/approve', [PayrollController::class, 'approveSalaryGeneration']);

        // Employee payroll routes
        Route::get('/{employee}', [PayrollController::class, 'show'])->whereNumber('employee');
        Route::post('/{employee}/payslip', [PayrollController::class, 'generatePayslip'])->whereNumber('employee');

        // Tax slabs
        Route::get('tax-slabs/export/pdf', [TaxSlabController::class, 'exportPdf'])->middleware('can:tax_slab.read');
        Route::get('tax-slabs/export/excel', [TaxSlabController::class, 'exportExcel'])->middleware('can:tax_slab.read');
        Route::get('tax-slabs', [TaxSlabController::class, 'index'])->middleware('can:tax_slab.read');
        Route::post('tax-slabs', [TaxSlabController::class, 'store'])->middleware('can:tax_slab.create');
        Route::get('tax-slabs/{taxSlab}', [TaxSlabController::class, 'show'])->middleware('can:tax_slab.read');
        Route::put('tax-slabs/{taxSlab}', [TaxSlabController::class, 'update'])->middleware('can:tax_slab.update');
        Route::delete('tax-slabs/{taxSlab}', [TaxSlabController::class, 'destroy'])->middleware('can:tax_slab.delete');
    });

    Route::get('payrollExport/pdf', [PayrollController::class, 'exportPdf']);
    Route::get('payrollExport/excel', [PayrollController::class, 'exportExcel']);

    // Payslip routes
    Route::prefix('payslip')->group(function () {
        Route::get('/', [PayrollController::class, 'payslip']);
        Route::post('/download/{employee}', [PayrollController::class, 'downloadPayslip']);
        Route::get('/download/{employee}', [PayrollController::class, 'downloadPayslipGet']);
        Route::options('/download/{employee}', function () {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        });
    });

    // Salary routes
    Route::prefix('salaries')->group(function () {
        Route::post('/', [PayrollController::class, 'storeSalary']);
        Route::get('/employee/{employee}', [PayrollController::class, 'getEmployeeSalaries']);
        Route::get('/employee/{employee}/current', [PayrollController::class, 'getCurrentSalary']);
        Route::put('/{salary}', [PayrollController::class, 'updateSalary']);
        Route::delete('/{salary}', [PayrollController::class, 'deleteSalary']);
    });

    // Employee salary data route (comprehensive data)
    Route::get('/employees/{employee}/salary-data', [PayrollController::class, 'getEmployeeSalaryData']);
    Route::get('/employees/{employee}/salary-history', [PayrollController::class, 'getSalaryHistory']);

    // PayslipType routes
    Route::prefix('payslip-types')->group(function () {
        Route::get('/', [PayslipTypeController::class, 'index']);
        Route::post('/', [PayslipTypeController::class, 'store']);
        Route::get('/{id}', [PayslipTypeController::class, 'show']);
        Route::put('/{id}', [PayslipTypeController::class, 'update']);
        Route::delete('/{id}', [PayslipTypeController::class, 'destroy']);
    });

    // Employee Allowance routes
    Route::prefix('employee-allowances')->group(function () {
        Route::get('/', [EmployeeAllowanceController::class, 'index']);
        Route::post('/', [EmployeeAllowanceController::class, 'store']);
        Route::get('/{id}', [EmployeeAllowanceController::class, 'show']);
        Route::put('/{id}', [EmployeeAllowanceController::class, 'update']);
        Route::delete('/{id}', [EmployeeAllowanceController::class, 'destroy']);
        Route::get('/employee/{employeeId}', [EmployeeAllowanceController::class, 'getByEmployee']);
        Route::get('/allowance/{allowanceId}', [EmployeeAllowanceController::class, 'getByAllowance']);
    });

    // Employee Deduction routes
    Route::prefix('employee-deductions')->group(function () {
        Route::get('/', [EmployeeDeductionController::class, 'index']);
        Route::post('/', [EmployeeDeductionController::class, 'store']);
        Route::get('/{id}', [EmployeeDeductionController::class, 'show']);
        Route::put('/{id}', [EmployeeDeductionController::class, 'update']);
        Route::delete('/{id}', [EmployeeDeductionController::class, 'destroy']);
        Route::get('/employee/{employeeId}', [EmployeeDeductionController::class, 'getByEmployee']);
        Route::get('/deduction/{deductionId}', [EmployeeDeductionController::class, 'getByDeduction']);
    });

    // Employee Loan routes
    Route::prefix('employee-loans')->group(function () {
        Route::get('/', [EmployeeLoanController::class, 'index']);
        Route::post('/', [EmployeeLoanController::class, 'store']);
        Route::get('/{id}', [EmployeeLoanController::class, 'show']);
        Route::put('/{id}', [EmployeeLoanController::class, 'update']);
        Route::delete('/{id}', [EmployeeLoanController::class, 'destroy']);
        Route::get('/employee/{employeeId}', [EmployeeLoanController::class, 'getByEmployee']);
        Route::get('/loan/{loanId}', [EmployeeLoanController::class, 'getByLoan']);
    });

    // Payroll routes (moved from main API routes)
    Route::apiResource('payrolls', PayslipController::class);
    Route::post('payrolls/generate', [PayslipController::class, 'generate']);

    Route::get('payroll-deductions', [PayrollController::class, 'deductions']);
    Route::patch('payroll-deductions/{id}/status', [PayrollController::class, 'updateDeductionStatus']);
});
