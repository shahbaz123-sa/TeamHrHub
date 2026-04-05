<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\Report\LatestReportController;
use Modules\CRM\Http\Controllers\Report\FinancialReportController;

Route::middleware('can:latest_report.read')->group(function () {
    Route::apiResource('latest-reports', LatestReportController::class)
        ->where(['id' => '[0-9]+', 'latest_report' => '[0-9]+']);
});

Route::middleware('can:financial_report.read')->group(function () {
    Route::apiResource('financial-reports', FinancialReportController::class)
        ->where(['id' => '[0-9]+', 'financial_report' => '[0-9]+']);
});
