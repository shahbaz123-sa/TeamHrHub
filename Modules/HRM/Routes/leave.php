<?php

use Illuminate\Support\Facades\Route;
use Modules\HRM\Http\Controllers\LeaveController;

Route::get('leaveExport/pdf', [LeaveController::class, 'exportPdf']);
Route::get('leaveExport/excel', [LeaveController::class, 'exportExcel']);
Route::get('leaveBalancesExport/pdf', [LeaveController::class, 'exportBalancesPdf']);
Route::get('leaveBalancesExport/excel', [LeaveController::class, 'exportBalancesExcel']);

Route::prefix('leaves')->group(function () {
    Route::get('balance/{employeeId}', [LeaveController::class, 'getUserLeaveBalance'])
        ->name('leaves.balance')
        ->middleware('can:leave.read');
    Route::get('balances', [LeaveController::class, 'leaveBalances'])
        ->name('leaves.balances')
        ->middleware('can:leave.read');
    Route::prefix('{leave}')->where(['leave' => '[0-9]+'])->group(function () {
        Route::post('manager/approve-reject', [LeaveController::class, 'approveRejectByManager']);
        Route::post('hr/approve-reject', [LeaveController::class, 'approveRejectByHr']);
    });
});
Route::apiResource('leaves', LeaveController::class)
    ->parameters([
        'leaves' => 'leave',
    ])
    ->where(['leave' => '[0-9]+']);
