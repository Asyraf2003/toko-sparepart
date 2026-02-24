<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Ports\Repositories\AuditLogQueryPort;
use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\ProductStockQueryPort;
use App\Application\Ports\Repositories\ProfitReportQueryPort;
use App\Application\Ports\Repositories\PurchasingReportQueryPort;
use App\Application\Ports\Repositories\SalesReportQueryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Repositories\StockReportQueryPort;
use App\Application\Ports\Repositories\TransactionPartLineRepositoryPort;
use App\Application\Ports\Repositories\TransactionRepositoryPort;
use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\LowStockNotifierPort;
use App\Application\Ports\Services\PdfRendererPort;
use App\Application\Ports\Services\TelegramSenderPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Notifications\Telegram\TelegramLowStockNotifier;
use App\Infrastructure\Notifications\Telegram\TelegramOpsSender;
use App\Infrastructure\Pdf\DompdfPdfRenderer;
use App\Infrastructure\Persistence\Eloquent\DatabaseTransactionManager;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogger;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryStockRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductStockQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProfitReportQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentPurchasingReportQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalesReportQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockLedgerRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockReportQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionPartLineRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTransactionServiceLineRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClockPort::class, SystemClock::class);
        $this->app->singleton(TransactionManagerPort::class, DatabaseTransactionManager::class);

        $this->app->bind(ProductRepositoryPort::class, EloquentProductRepository::class);
        $this->app->bind(InventoryStockRepositoryPort::class, EloquentInventoryStockRepository::class);
        $this->app->bind(StockLedgerRepositoryPort::class, EloquentStockLedgerRepository::class);
        $this->app->bind(ProductStockQueryPort::class, EloquentProductStockQuery::class);

        $this->app->bind(TransactionRepositoryPort::class, EloquentTransactionRepository::class);
        $this->app->bind(TransactionPartLineRepositoryPort::class, EloquentTransactionPartLineRepository::class);
        $this->app->bind(TransactionServiceLineRepositoryPort::class, EloquentTransactionServiceLineRepository::class);

        $this->app->bind(SalesReportQueryPort::class, EloquentSalesReportQuery::class);
        $this->app->bind(PurchasingReportQueryPort::class, EloquentPurchasingReportQuery::class);
        $this->app->bind(StockReportQueryPort::class, EloquentStockReportQuery::class);
        $this->app->bind(ProfitReportQueryPort::class, EloquentProfitReportQuery::class);

        $this->app->singleton(PdfRendererPort::class, DompdfPdfRenderer::class);
        $this->app->singleton(LowStockNotifierPort::class, TelegramLowStockNotifier::class);
        $this->app->singleton(TelegramSenderPort::class, TelegramOpsSender::class);
        $this->app->singleton(AuditLoggerPort::class, EloquentAuditLogger::class);
        $this->app->singleton(AuditLogQueryPort::class, EloquentAuditLogQuery::class);
    }

    public function boot(): void
    {
        //
    }
}