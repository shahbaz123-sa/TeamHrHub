<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\Notice\TypeController;
use Modules\CRM\Http\Controllers\NoticeController;

Route::middleware('can:notice_type.read')->group(function () {
    Route::apiResource('notice/types', TypeController::class)
        ->where(['id' => '[0-9]+', 'notice_type' => '[0-9]+']);
});

Route::middleware('can:notice.read')->group(function () {
    Route::apiResource('notices', NoticeController::class)
        ->where(['id' => '[0-9]+']);
});
