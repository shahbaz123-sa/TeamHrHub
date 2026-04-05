<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CreditApplicationController;

Route::middleware('can:credit_application.read')->group(function () {
    Route::get('credit-applications', [CreditApplicationController::class, 'index']);

    Route::get('credit-application/form-submissions', [CreditApplicationController::class, 'listFormSubmissions']);
    Route::get('credit-application/form-submissions/{id}', [CreditApplicationController::class, 'findFormSubmissionById']);

    Route::prefix('credit-applications')->group(function () {

        Route::prefix('{id}')->group(function () {

            Route::get('', [CreditApplicationController::class, 'show'])
                ->where(['id' => '[0-9]+']);

            Route::patch('approve', [CreditApplicationController::class, 'approve'])
                ->middleware('can:credit_application.update')
                ->where(['id' => '[0-9]+']);

            Route::patch('reject', [CreditApplicationController::class, 'reject'])
                ->middleware('can:credit_application.update')
                ->where(['id' => '[0-9]+']);
        });

        Route::prefix('company/{companyId}')->group(function () {

            Route::get('', [CreditApplicationController::class, 'getByCompanyId'])
                ->where(['companyId' => '[0-9]+']);

            Route::post('bulk-approve', [CreditApplicationController::class, 'bulkApprove'])
                ->middleware('can:credit_application.update')
                ->where(['companyId' => '[0-9]+']);

            Route::post('bulk-reject', [CreditApplicationController::class, 'bulkReject'])
                ->middleware('can:credit_application.update')
                ->where(['companyId' => '[0-9]+']);
        });

        Route::get('widgets/status-counts', [CreditApplicationController::class, 'widgetStatusCounts'])
            ->middleware('can:credit_application.read');
    });
});
