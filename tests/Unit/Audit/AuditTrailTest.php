<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use App\Application\Ports\Repositories\InventoryStockRepositoryPort;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Repositories\StockLedgerRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Catalog\SetSellingPriceRequest;
use App\Application\UseCases\Catalog\SetSellingPriceUseCase;
use App\Application\UseCases\Inventory\AdjustStockRequest;
use App\Application\UseCases\Inventory\AdjustStockUseCase;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use App\Application\UseCases\Sales\VoidTransactionRequest;
use App\Application\UseCases\Sales\VoidTransactionUseCase;
use App\Domain\Audit\AuditEntry;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class AuditTrailTest extends TestCase
{
    public function test_audit_logger_saves_before_after(): void
    {
        /** @var AuditLoggerPort $audit */
        $audit = app(AuditLoggerPort::class);

        $audit->append(new AuditEntry(
            actorId: 1,
            actorRole: 'ADMIN',
            entityType: 'TestEntity',
            entityId: 99,
            action: 'TEST',
            reason: 'unit test',
            before: ['a' => 1],
            after: ['a' => 2],
            meta: ['k' => 'v'],
        ));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'TEST',
            'entity_type' => 'TestEntity',
            'entity_id' => 99,
            'reason' => 'unit test',
        ]);
    }

    public function test_void_transaction_without_reason_is_rejected(): void
    {
        $uc = new VoidTransactionUseCase(
            tx: $this->mk(TransactionManagerPort::class),
            clock: $this->mk(ClockPort::class),
            lowStock: $this->mk(NotifyLowStockForProductUseCase::class),
            audit: $this->mk(AuditLoggerPort::class),
        );

        $this->expectException(\InvalidArgumentException::class);

        $uc->handle(new VoidTransactionRequest(
            transactionId: 1,
            actorUserId: 1,
            reason: '   ',
        ));
    }

    public function test_set_selling_price_without_note_is_rejected(): void
    {
        $uc = new SetSellingPriceUseCase(
            tx: $this->mk(TransactionManagerPort::class),
            products: $this->mk(ProductRepositoryPort::class),
            audit: $this->mk(AuditLoggerPort::class),
        );

        $this->expectException(\InvalidArgumentException::class);

        $uc->handle(new SetSellingPriceRequest(
            productId: 1,
            sellPriceCurrent: 1000,
            actorUserId: 1,
            note: '   ',
        ));
    }

    public function test_adjust_stock_without_note_is_rejected(): void
    {
        $uc = new AdjustStockUseCase(
            tx: $this->mk(TransactionManagerPort::class),
            clock: $this->mk(ClockPort::class),
            products: $this->mk(ProductRepositoryPort::class),
            stocks: $this->mk(InventoryStockRepositoryPort::class),
            ledger: $this->mk(StockLedgerRepositoryPort::class),
            audit: $this->mk(AuditLoggerPort::class),
            lowStock: null,
        );

        $this->expectException(\InvalidArgumentException::class);

        $uc->handle(new AdjustStockRequest(
            productId: 1,
            qtyDelta: 1,
            actorUserId: 1,
            note: '   ',
            refType: 'unit_test',
            refId: null,
        ));
    }

    /**
     * Helper untuk PHPUnit mock (hindari bentrok dengan TestCase::mock()).
     *
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T&MockObject
     */
    private function mk(string $class): MockObject
    {
        /** @var T&MockObject $m */
        $m = $this->createMock($class);

        return $m;
    }
}
