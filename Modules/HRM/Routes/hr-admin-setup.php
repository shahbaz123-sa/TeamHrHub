<?php

use Illuminate\Support\Facades\Route;
use Modules\HRM\Http\Controllers\{
    BranchController,
    DepartmentController,
    DesignationController,
    LeaveTypeController,
    DocumentTypeController,
    PayslipTypeController,
    AllowanceController,
    LoanOptionController,
    DeductionController,
    GoalTypeController,
    TrainingTypeController,
    AwardTypeController,
    TerminationTypeController,
    JobCategoryController,
    JobStageController,
    PerformanceTypeController,
    CompetencyController,
    ExpenseTypeController,
    TicketCategoryController,
    AssetTypeController,
    EmploymentTypeController,
    EmploymentStatusController,
    HolidayController
};

// HRM Type Resources
Route::apiResource('branches', BranchController::class);
Route::prefix('branches')->group(function () {
    Route::post('allow-remote', [BranchController::class, 'allowRemote']);
    Route::post('disallow-remote', [BranchController::class, 'disallowRemote']);
});

Route::apiResource('departments', DepartmentController::class);
Route::apiResource('designations', DesignationController::class);
Route::apiResource('leave-types', LeaveTypeController::class);
Route::post('leave-types/{id}/reorder', [LeaveTypeController::class, 'reorder']);
Route::apiResource('document-types', DocumentTypeController::class);
Route::apiResource('payslip-types', PayslipTypeController::class);
Route::apiResource('allowances', AllowanceController::class);
Route::apiResource('loan-options', LoanOptionController::class);
Route::apiResource('deductions', DeductionController::class);
Route::apiResource('goal-types', GoalTypeController::class);
Route::apiResource('training-types', TrainingTypeController::class);
Route::apiResource('award-types', AwardTypeController::class);
Route::apiResource('termination-types', TerminationTypeController::class);
Route::apiResource('job-categories', JobCategoryController::class);
Route::apiResource('job-stages', JobStageController::class);
Route::apiResource('performance-types', PerformanceTypeController::class);
Route::apiResource('competencies', CompetencyController::class);
Route::apiResource('expense-types', ExpenseTypeController::class);
Route::apiResource('asset-types', AssetTypeController::class);
Route::apiResource('employment-types', EmploymentTypeController::class);
Route::apiResource('ticket-categories', TicketCategoryController::class);
Route::apiResource('employment-statuses', EmploymentStatusController::class);
Route::apiResource('holidays', HolidayController::class);
