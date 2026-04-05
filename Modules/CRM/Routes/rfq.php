<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\RfqController;

Route::middleware('can:rfq.read')->group(function () {
    Route::get('rfqs', [RfqController::class, 'index']);

    Route::get('rfq/form-submissions', [RfqController::class, 'listFormSubmissions']);
    Route::get('rfq/form-submissions/{id}', [RfqController::class, 'findFormSubmissionById']);

    Route::prefix('rfqs')->group(function () {

        Route::prefix('{id}')->group(function () {
            Route::get('', [RfqController::class, 'show'])->where(['id' => '[0-9]+']);
            Route::patch('assign-manager', [RfqController::class, 'assignManager'])
                ->middleware('can:rfq.update')
                ->where(['id' => '[0-9]+']);
            Route::post('send-quotation', [RfqController::class, 'sendQuotation'])
                ->middleware('can:rfq.update')
                ->where(['id' => '[0-9]+']);
        });

        Route::get('widgets/status-counts', [RfqController::class, 'widgetStatusCounts'])
            ->middleware('can:rfq.read');
    });
});
