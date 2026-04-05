<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\OrderController;

Route::middleware('can:order.read')->group(function () {
    Route::apiResource('orders', OrderController::class)
        ->where(['id' => '[0-9]+', 'order' => '[0-9]+']);

    Route::prefix('orders')->group(function () {
        Route::patch('{order}/status', [OrderController::class, 'changeStatus'])
            ->where(['order' => '[0-9]+'])
            ->middleware('can:order.update');

        Route::patch('{order}/payment-received', [OrderController::class, 'markPaymentReceived'])
            ->where(['order' => '[0-9]+'])
            ->middleware('can:order.update');

        Route::post('bulk/change-status', [OrderController::class, 'bulkChangeStatus'])
            ->middleware('can:order.update');

        Route::post('bulk/mark-payment-received', [OrderController::class, 'bulkMarkPaymentReceived'])
            ->middleware('can:order.update');

        Route::get('widgets/status-counts', [OrderController::class, 'widgetStatusCounts'])
            ->middleware('can:order.read');
    });
});
