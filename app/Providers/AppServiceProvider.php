<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\ProductStockQueryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Repositories\TransactionPartLineRepositoryPort;
use App\Application\Ports\Repositories\TransactionRepositoryPort;
use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Persistence\Eloquent\DatabaseTransactionManager;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentInventoryStockRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductStockQuery;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentStockLedgerRepository;
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
    }

    public function boot(): void
    {
        //
    }
}
