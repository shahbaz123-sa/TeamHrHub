<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;
use Modules\Chat\Http\Controllers\Chat\MessageController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('chats', [ChatController::class, 'index'])
        ->middleware('can:chat.read');

    Route::prefix('chat')->group(function () {

        Route::prefix('messages')->group(function () {
            Route::get('', [MessageController::class, 'index'])
                ->middleware('can:chat.read');

            Route::post('', [MessageController::class, 'store'])
                ->middleware('can:chat.create');

            Route::post('upload-attachment', [MessageController::class, 'uploadAttachment'])
                ->middleware('can:chat.create');
        });
    });
});
