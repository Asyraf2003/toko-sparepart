<?php

use App\Interfaces\Web\Controllers\Cashier\CashierDashboardController;
use App\Interfaces\Web\Controllers\Cashier\ProductSearchController;
use App\Interfaces\Web\Controllers\Cashier\TransactionCompleteCashController;
use App\Interfaces\Web\Controllers\Cashier\TransactionCompleteTransferController;
use App\Interfaces\Web\Controllers\Cashier\TransactionCreateController;
use App\Interfaces\Web\Controllers\Cashier\TransactionOpenController;
use App\Interfaces\Web\Controllers\Cashier\TransactionPartLineDeleteController;
use App\Interfaces\Web\Controllers\Cashier\TransactionPartLineStoreController;
use App\Interfaces\Web\Controllers\Cashier\TransactionPartLineUpdateQtyController;
use App\Interfaces\Web\Controllers\Cashier\TransactionServiceLineDeleteController;
use App\Interfaces\Web\Controllers\Cashier\TransactionServiceLineStoreController;
use App\Interfaces\Web\Controllers\Cashier\TransactionServiceLineUpdateController;
use App\Interfaces\Web\Controllers\Cashier\TransactionShowController;
use App\Interfaces\Web\Controllers\Cashier\TransactionTodayController;
use App\Interfaces\Web\Controllers\Cashier\TransactionVoidController;
use App\Interfaces\Web\Controllers\Cashier\TransactionWorkOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:CASHIER'])->prefix('cashier')->group(function () {
    Route::get('/', fn () => redirect('/cashier/dashboard'));

    Route::get('/dashboard', CashierDashboardController::class);
    Route::get('/transactions/today', TransactionTodayController::class);

    Route::post('/transactions', TransactionCreateController::class);
    Route::get('/transactions/{transactionId}', TransactionShowController::class);
    Route::get('/products/search', ProductSearchController::class);

    Route::post('/transactions/{transactionId}/open', TransactionOpenController::class);
    Route::post('/transactions/{transactionId}/complete-cash', TransactionCompleteCashController::class);
    Route::post('/transactions/{transactionId}/complete-transfer', TransactionCompleteTransferController::class);
    Route::post('/transactions/{transactionId}/void', TransactionVoidController::class);

    Route::post('/transactions/{transactionId}/part-lines', TransactionPartLineStoreController::class);
    Route::post('/transactions/{transactionId}/part-lines/{lineId}/qty', TransactionPartLineUpdateQtyController::class);
    Route::post('/transactions/{transactionId}/part-lines/{lineId}/delete', TransactionPartLineDeleteController::class);
    Route::post('/transactions/{transactionId}/service-lines', TransactionServiceLineStoreController::class);
    Route::post('/transactions/{transactionId}/service-lines/{lineId}/update', TransactionServiceLineUpdateController::class);
    Route::post('/transactions/{transactionId}/service-lines/{lineId}/delete', TransactionServiceLineDeleteController::class);

    Route::get('/transactions/{transactionId}/work-order', TransactionWorkOrderController::class);
});
