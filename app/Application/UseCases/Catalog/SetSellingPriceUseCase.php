<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\DTO\Inventory\ProductSnapshot;
use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;

final readonly class SetSellingPriceUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ProductRepositoryPort $products,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(SetSellingPriceRequest $req): ProductSnapshot
    {
        $note = trim($req->note);
        if ($note === '') {
            throw new \InvalidArgumentException('note is required');
        }

        if ($req->sellPriceCurrent < 0) {
            throw new \InvalidArgumentException('sellPriceCurrent must be >= 0');
        }

        return $this->tx->run(function () use ($req, $note): ProductSnapshot {
            $before = $this->products->findById($req->productId);

            $after = $this->products->setSellingPrice(
                productId: $req->productId,
                sellPriceCurrent: $req->sellPriceCurrent,
            );

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: null,
                entityType: 'Product',
                entityId: $req->productId,
                action: 'PRICE_CHANGE',
                reason: $note,
                before: self::snapshot($before),
                after: self::snapshot($after),
                meta: [
                    'sell_price_current' => $req->sellPriceCurrent,
                ],
            ));

            return $after;
        });
    }

    private static function snapshot(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }

        // Best-effort snapshot untuk DTO readonly/public props
        try {
            $json = json_encode($value, JSON_THROW_ON_ERROR);
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : ['value' => $decoded];
        } catch (\Throwable) {
            return ['__type' => get_debug_type($value)];
        }
    }
}
