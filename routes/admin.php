<?php

use App\Interfaces\Web\Controllers\Admin\ProductAdjustStockController;
use App\Interfaces\Web\Controllers\Admin\ProductCreateController;
use App\Interfaces\Web\Controllers\Admin\ProductEditController;
use App\Interfaces\Web\Controllers\Admin\ProductSetPriceController;
use App\Interfaces\Web\Controllers\Admin\ProductSetThresholdController;
use App\Interfaces\Web\Controllers\Admin\ProductStockIndexController;
use App\Interfaces\Web\Controllers\Admin\ProductStoreController;
use App\Interfaces\Web\Controllers\Admin\ProductUpdateController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceCreateController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceIndexController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceStoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect('/admin/products'));

    Route::get('/products', ProductStockIndexController::class);
    Route::get('/products/create', ProductCreateController::class);
    Route::post('/products', ProductStoreController::class);

    Route::get('/products/{productId}/edit', ProductEditController::class);
    Route::post('/products/{productId}', ProductUpdateController::class);
    Route::post('/products/{productId}/selling-price', ProductSetPriceController::class);
    Route::post('/products/{productId}/min-threshold', ProductSetThresholdController::class);
    Route::post('/products/{productId}/adjust-stock', ProductAdjustStockController::class);

    Route::get('/purchases', PurchaseInvoiceIndexController::class);
    Route::get('/purchases/create', PurchaseInvoiceCreateController::class);
    Route::post('/purchases', PurchaseInvoiceStoreController::class);
});
