<?php

use Illuminate\Support\Facades\Route;
use Modules\HRM\Http\Controllers\EmployeeController;

// Core HRM Resources
Route::apiResource('employees', EmployeeController::class)
    ->where(['id' => '[0-9]+', 'employee' => '[0-9]+']);

Route::get('employeeExport/pdf', [EmployeeController::class, 'exportPdf']);
Route::get('employeeExport/excel', [EmployeeController::class, 'exportExcel']);

Route::prefix('employees')->group(function () {
    // Employee Relationship Resources
    // Route::apiResource('dependents', DependentController::class)->shallow();
    // Route::apiResource('documents', EmployeeDocumentController::class)->shallow();
    // Route::apiResource('loans', EmployeeLoanController::class)->shallow();
    // Route::apiResource('allowances', EmployeeAllowanceController::class)->shallow();
    // Route::apiResource('deductions', EmployeeDeductionController::class)->shallow();

    // Employee Assign Roles
    Route::get('get-all', [EmployeeController::class, 'getAllEmployees']);
    Route::get('rfq-managers', [EmployeeController::class, 'getRfqManagers']);
    Route::post('assign-roles', [EmployeeController::class, 'assignRoles']);
    Route::post('update-att-exemption', [EmployeeController::class, 'updateAttExemption']);
    Route::get('with-roles', [EmployeeController::class, 'withRoles'])->name('hrm.employee.with-roles');
    Route::get('logged-in', [EmployeeController::class, 'loggedIn'])->name('hrm.employee.logged-in');

    Route::prefix('{employee}')->group(function () {
        // Employee Asset Assignment
        Route::prefix('assets/{asset}')->group(function () {
            Route::post('assign', [EmployeeController::class, 'assignAsset']);
            Route::post('return', [EmployeeController::class, 'returnAsset']);
        });

        Route::get('roles', [EmployeeController::class, 'getRolesByEmployee']);

        // Special Employee Routes
        Route::post('terminate', [EmployeeController::class, 'terminate']);
        Route::post('suspend', [EmployeeController::class, 'suspend']);
        Route::post('activate', [EmployeeController::class, 'activate']);
        Route::get('payslips', [EmployeeController::class, 'payslips']);
    });
});
