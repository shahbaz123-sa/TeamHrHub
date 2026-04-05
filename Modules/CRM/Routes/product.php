<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Helper\ProductHelper;
use Modules\CRM\Http\Controllers\Product\Attribute\ValueController;
use Modules\CRM\Http\Controllers\Product\AttributeController;
use Modules\CRM\Http\Controllers\Product\BrandController;
use Modules\CRM\Http\Controllers\Product\CategoryController;
use Modules\CRM\Http\Controllers\Product\CityController;
use Modules\CRM\Http\Controllers\Product\DailyPriceController;
use Modules\CRM\Http\Controllers\Product\GraphPriceController;
use Modules\CRM\Http\Controllers\Product\TagController;
use Modules\CRM\Http\Controllers\Product\UnitOfMeasurementController;
use Modules\CRM\Http\Controllers\ProductController;
use Modules\CRM\Models\Product;

Route::apiResource('products', ProductController::class)
    ->where(['id' => '[0-9]+', 'product' => '[0-9]+']);

Route::prefix('product')->group(function () {

    Route::get('parents', [ProductController::class, 'getParents']);

    Route::get('/sku/generate', function () {
        return response()->json([
            'sku' => ProductHelper::generateSku(new Product())
        ]);
    });

    Route::middleware('can:product_tag.read')->group(function () {
        Route::apiResource('tags', TagController::class)->where(['id' => '[0-9]+', 'tag' => '[0-9]+']);
    });

    Route::middleware('can:product_category.read')->group(function () {
        Route::apiResource('categories', CategoryController::class)->where(['id' => '[0-9]+', 'category' => '[0-9]+']);
        Route::get('category/parents', [CategoryController::class, 'getParents']);
    });

    Route::middleware('can:product_uom.read')->group(function () {
        Route::apiResource('uoms', UnitOfMeasurementController::class)->where(['id' => '[0-9]+', 'uom' => '[0-9]+']);
    });

    Route::middleware('can:product_attribute.read')->group(function () {
        Route::apiResource('attributes', AttributeController::class)->where(['id' => '[0-9]+', 'attribute' => '[0-9]+']);
    });

    Route::middleware('can:product_brand.read')->group(function () {
        Route::apiResource('brands', BrandController::class)->where(['id' => '[0-9]+', 'brand' => '[0-9]+']);
    });

    Route::middleware('can:product_graph_price.read')->group(function () {
        Route::apiResource('graph-prices', GraphPriceController::class)->where(['id' => '[0-9]+', 'graph_price' => '[0-9]+']);

        Route::prefix('graph-prices')->group(function () {
            Route::get('filters', [GraphPriceController::class, 'getFilters']);
            Route::post('import', [GraphPriceController::class, 'import'])->middleware('can:product_graph_price.create');
        });
    });

    Route::middleware('can:product_daily_price.read')->group(function () {
        Route::apiResource('daily-prices', DailyPriceController::class)->where(['id' => '[0-9]+', 'daily_price' => '[0-9]+']);

        Route::prefix('daily-prices')->group(function () {
            Route::post('import', [DailyPriceController::class, 'import'])->middleware('can:product_daily_price.create');
            Route::get('filters', [DailyPriceController::class, 'getFilters']);

            Route::get('products', [DailyPriceController::class, 'getProducts']);

            Route::prefix('batch/{batchId}')->group(function () {
                Route::get('', [DailyPriceController::class, 'getBatchProducts']);
                Route::get('export', [DailyPriceController::class, 'export']);

                Route::middleware('can:product_daily_price.update')->group(function () {
                    Route::post('approve', [DailyPriceController::class, 'approve']);
                    Route::post('reject', [DailyPriceController::class, 'reject']);
                });
            });
        });
    });

    Route::middleware('can:product_city.read')->group(function () {
        Route::apiResource('cities', CityController::class)->where(['id' => '[0-9]+', 'city' => '[0-9]+']);
    });

    Route::middleware('can:product_attribute_value.read')->group(function () {
        Route::apiResource('attribute/values', ValueController::class)->where(['id' => '[0-9]+', 'value' => '[0-9]+']);
    });
});
