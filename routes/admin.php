<?php

declare(strict_types=1);

use App\Interfaces\Web\Controllers\Admin\AdminDashboardController;
use App\Interfaces\Web\Controllers\Admin\AuditLogIndexController;
use App\Interfaces\Web\Controllers\Admin\AuditLogShowController;
use App\Interfaces\Web\Controllers\Admin\EmployeeCreateController;
use App\Interfaces\Web\Controllers\Admin\EmployeeIndexController;
use App\Interfaces\Web\Controllers\Admin\EmployeeLoanCreateController;
use App\Interfaces\Web\Controllers\Admin\EmployeeLoanStoreController;
use App\Interfaces\Web\Controllers\Admin\EmployeeStoreController;
use App\Interfaces\Web\Controllers\Admin\ExpenseCreateController;
use App\Interfaces\Web\Controllers\Admin\ExpenseIndexController;
use App\Interfaces\Web\Controllers\Admin\ExpenseStoreController;
use App\Interfaces\Web\Controllers\Admin\PayrollPeriodCreateController;
use App\Interfaces\Web\Controllers\Admin\PayrollPeriodEditController;
use App\Interfaces\Web\Controllers\Admin\PayrollPeriodIndexController;
use App\Interfaces\Web\Controllers\Admin\PayrollPeriodShowController;
use App\Interfaces\Web\Controllers\Admin\PayrollPeriodStoreController;
use App\Interfaces\Web\Controllers\Admin\PayrollPeriodUpdateController;
use App\Interfaces\Web\Controllers\Admin\ProductAdjustStockController;
use App\Interfaces\Web\Controllers\Admin\ProductCreateController;
use App\Interfaces\Web\Controllers\Admin\ProductEditController;
use App\Interfaces\Web\Controllers\Admin\ProductSetPriceController;
use App\Interfaces\Web\Controllers\Admin\ProductSetThresholdController;
use App\Interfaces\Web\Controllers\Admin\ProductStockIndexController;
use App\Interfaces\Web\Controllers\Admin\ProductStoreController;
use App\Interfaces\Web\Controllers\Admin\ProductUpdateController;
use App\Interfaces\Web\Controllers\Admin\ProfitReportIndexController;
use App\Interfaces\Web\Controllers\Admin\ProfitReportPdfController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceCreateController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceEditController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceIndexController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceMarkPaidController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceMarkUnpaidController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceShowController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceStoreController;
use App\Interfaces\Web\Controllers\Admin\PurchaseInvoiceUpdateController;
use App\Interfaces\Web\Controllers\Admin\PurchasingReportIndexController;
use App\Interfaces\Web\Controllers\Admin\PurchasingReportPdfController;
use App\Interfaces\Web\Controllers\Admin\SalesReportIndexController;
use App\Interfaces\Web\Controllers\Admin\SalesReportPdfController;
use App\Interfaces\Web\Controllers\Admin\StockReportIndexController;
use App\Interfaces\Web\Controllers\Admin\StockReportPdfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->group(function (): void {
    Route::get('/', fn () => redirect('/admin/dashboard'));

    Route::get('/dashboard', AdminDashboardController::class);

    Route::get('/audit-logs', AuditLogIndexController::class);
    Route::get('/audit-logs/{auditLogId}', AuditLogShowController::class);

    Route::get('/employees', EmployeeIndexController::class);
    Route::post('/employees', EmployeeStoreController::class);
    Route::get('/employees/create', EmployeeCreateController::class);
    Route::get('/employees/{employeeId}/loans/create', EmployeeLoanCreateController::class);
    Route::post('/employees/{employeeId}/loans', EmployeeLoanStoreController::class);

    Route::get('/expenses', ExpenseIndexController::class);
    Route::post('/expenses', ExpenseStoreController::class);
    Route::get('/expenses/create', ExpenseCreateController::class);

    Route::get('/payroll', PayrollPeriodIndexController::class);
    Route::post('/payroll', PayrollPeriodStoreController::class);
    Route::get('/payroll/create', PayrollPeriodCreateController::class);

    Route::get('/payroll/{payrollPeriodId}', PayrollPeriodShowController::class);
    Route::get('/payroll/{payrollPeriodId}/edit', PayrollPeriodEditController::class);
    Route::post('/payroll/{payrollPeriodId}', PayrollPeriodUpdateController::class);

    Route::get('/products', ProductStockIndexController::class);
    Route::post('/products', ProductStoreController::class);
    Route::get('/products/create', ProductCreateController::class);
    Route::get('/products/{productId}/edit', ProductEditController::class);
    Route::post('/products/{productId}', ProductUpdateController::class);
    Route::post('/products/{productId}/adjust-stock', ProductAdjustStockController::class);
    Route::post('/products/{productId}/min-threshold', ProductSetThresholdController::class);
    Route::post('/products/{productId}/selling-price', ProductSetPriceController::class);

    Route::get('/purchases', PurchaseInvoiceIndexController::class);
    Route::post('/purchases', PurchaseInvoiceStoreController::class);
    Route::get('/purchases/create', PurchaseInvoiceCreateController::class);
    Route::get('/purchases/{purchaseInvoiceId}', PurchaseInvoiceShowController::class);
    Route::get('/purchases/{purchaseInvoiceId}/edit', PurchaseInvoiceEditController::class);
    Route::post('/purchases/{purchaseInvoiceId}', PurchaseInvoiceUpdateController::class);

    Route::get('/reports/sales', SalesReportIndexController::class);
    Route::get('/reports/sales/pdf', SalesReportPdfController::class);

    Route::get('/reports/purchasing', PurchasingReportIndexController::class);
    Route::get('/reports/purchasing/pdf', PurchasingReportPdfController::class);

    Route::get('/reports/stock', StockReportIndexController::class);
    Route::get('/reports/stock/pdf', StockReportPdfController::class);

    Route::get('/reports/profit', ProfitReportIndexController::class);
    Route::get('/reports/profit/pdf', ProfitReportPdfController::class);

    Route::post('/purchases/{purchaseInvoiceId}/mark-paid', PurchaseInvoiceMarkPaidController::class);
    Route::post('/purchases/{purchaseInvoiceId}/mark-unpaid', PurchaseInvoiceMarkUnpaidController::class);
});
