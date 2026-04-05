<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\SupplierController;
use Modules\CRM\Http\Controllers\EmailSettingController;
use Modules\CRM\Http\Controllers\FormSubmissionController;
use Modules\CRM\Http\Controllers\Wordpress\AuthController;
use Modules\CRM\Http\Controllers\Product\GraphPriceController;

Route::middleware('auth:sanctum')->group(function () {

    require_once 'product.php';
    require_once 'report.php';
    require_once 'notice.php';
    require_once 'order.php';
    require_once 'rfq.php';
    require_once 'post.php';
    require_once 'customer.php';
    require_once 'credit_application.php';

    Route::apiResource('form-submissions', FormSubmissionController::class);

    Route::apiResource('suppliers', SupplierController::class)
        ->middleware('can:supplier.read')
        ->where(['supplier' => '[0-9]+']);

    Route::middleware('can:email_setting.read')->group(function () {
        Route::apiResource('email-settings', EmailSettingController::class)
            ->where(['id' => '[0-9]+', 'email_setting' => '[0-9]+']);
    });
});

Route::post('crm/user/wp-login', [AuthController::class, 'login']);
Route::get('product/graph-prices/analytics', [GraphPriceController::class, 'generateAnalytics']);
Route::get('product/graph/daily-prices', [GraphPriceController::class, 'fetchDailyPrices']);
