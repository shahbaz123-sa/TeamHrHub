<?php

use Modules\CRM\Http\Controllers\Post\CategoryController;
use Modules\CRM\Http\Controllers\PostController;
use Modules\CRM\Http\Controllers\Post\TagController;

Route::middleware('can:post.read')->group(function () {
    Route::apiResource('posts', PostController::class)
        ->where(['id' => '[0-9]+', 'post' => '[0-9]+']);
});

Route::prefix('post')->group(function () {
    Route::middleware('can:post_tag.read')->group(function () {
        Route::apiResource('tags', TagController::class)->where(['id' => '[0-9]+', 'tag' => '[0-9]+']);
    });

    Route::middleware('can:post_category.read')->group(function () {
        Route::apiResource('categories', CategoryController::class)->where(['id' => '[0-9]+', 'category' => '[0-9]+']);
    });
});
