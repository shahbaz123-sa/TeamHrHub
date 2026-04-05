<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CustomerController;

Route::middleware('can:customer.read')->group(function () {
    Route::apiResource('customers', CustomerController::class)
        ->where(['id' => '[0-9]+', 'customer' => '[0-9]+']);

    Route::prefix('customers')->group(function () {
        Route::prefix('{id}')->middleware('can:customer.update')->group(function () {
            Route::patch('status', [CustomerController::class, 'updateStatus'])
                ->where(['id' => '[0-9]+']);

            Route::patch('company/status', [CustomerController::class, 'updateCompanyStatus'])
                ->where(['id' => '[0-9]+']);

            Route::post('company/documents', [CustomerController::class, 'uploadCompanyDocument'])
                ->where(['id' => '[0-9]+']);

            Route::delete('company/documents/{docId}', [CustomerController::class, 'deleteCompanyDocument'])
                ->where(['id' => '[0-9]+', 'docId' => '[0-9]+']);
        });

        Route::get('widgets/status-counts', [CustomerController::class, 'widgetStatusCounts']);
    });
});
