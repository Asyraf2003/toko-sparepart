<?php

declare(strict_types=1);

namespace App\Application\UseCases\Notifications;

use App\Application\DTO\Notifications\LowStockAlertMessage;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\LowStockNotifierPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class NotifyLowStockForProductUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private LowStockNotifierPort $notifier,
    ) {}

    public function handle(NotifyLowStockForProductRequest $req): void
    {
        if ($req->productId <= 0) {
            throw new \InvalidArgumentException('invalid product id');
        }
        if (trim($req->triggerType) === '') {
            throw new \InvalidArgumentException('triggerType is required');
        }

        // actorUserId boleh null (mis. dari test lama / sistem). Notifikasi tidak membutuhkan actor.
        if (DB::transactionLevel() === 0) {
            $this->tx->run(fn () => $this->handleInTx($req));

            return;
        }

        $this->handleInTx($req);
    }

    private function handleInTx(NotifyLowStockForProductRequest $req): void
    {
        $now = $this->clock->now();
        $nowStr = $now->format('Y-m-d H:i:s');

        $product = DB::table('products')
            ->where('id', $req->productId)
            ->first(['id', 'sku', 'name', 'min_stock_threshold', 'is_active']);

        if ($product === null) {
            return;
        }

        if ((bool) $product->is_active === false) {
            return;
        }

        $stock = DB::table('inventory_stocks')
            ->where('product_id', $req->productId)
            ->lockForUpdate()
            ->first(['on_hand_qty', 'reserved_qty']);

        if ($stock === null) {
            return;
        }

        $onHand = (int) $stock->on_hand_qty;
        $reserved = (int) $stock->reserved_qty;
        $available = $onHand - $reserved;

        $threshold = (int) $product->min_stock_threshold;

        $resetOnRecover = (bool) config('services.telegram_low_stock.reset_on_recover', true);

        if ($available > $threshold) {
            if ($resetOnRecover) {
                DB::table('low_stock_notification_states')
                    ->where('product_id', $req->productId)
                    ->delete();
            }

            return;
        }

        $state = DB::table('low_stock_notification_states')
            ->where('product_id', $req->productId)
            ->lockForUpdate()
            ->first(['product_id', 'last_notified_at', 'last_notified_available_qty']);

        if ($state === null) {
            try {
                DB::table('low_stock_notification_states')->insert([
                    'product_id' => $req->productId,
                    'last_notified_at' => null,
                    'last_notified_available_qty' => null,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);
            } catch (QueryException) {
                // race insert, ignore
            }

            $state = DB::table('low_stock_notification_states')
                ->where('product_id', $req->productId)
                ->lockForUpdate()
                ->first(['product_id', 'last_notified_at', 'last_notified_available_qty']);
        }

        $minIntervalSeconds = (int) config('services.telegram_low_stock.min_interval_seconds', 86400);
        if ($minIntervalSeconds < 0) {
            $minIntervalSeconds = 0;
        }

        $lastAt = null;
        if ($state !== null && $state->last_notified_at !== null && (string) $state->last_notified_at !== '') {
            $lastAt = new \DateTimeImmutable((string) $state->last_notified_at);
        }

        $lastAvail = null;
        if ($state !== null && $state->last_notified_available_qty !== null) {
            $lastAvail = (int) $state->last_notified_available_qty;
        }

        $canByTime = ($lastAt === null)
            ? true
            : (($now->getTimestamp() - $lastAt->getTimestamp()) >= $minIntervalSeconds);

        $moreCritical = ($lastAvail !== null) && ($available < $lastAvail);

        if (! ($canByTime || $moreCritical)) {
            return;
        }

        $msg = new LowStockAlertMessage(
            productId: (int) $product->id,
            sku: (string) $product->sku,
            name: (string) $product->name,
            availableQty: $available,
            threshold: $threshold,
            triggerType: $req->triggerType,
            occurredAt: $now,
        );

        $sent = false;

        try {
            $this->notifier->notifyLowStock($msg);
            $sent = true;
        } catch (\Throwable) {
            $sent = false;
        }

        $throttleOnFailure = (bool) config('services.telegram_low_stock.throttle_on_failure', true);

        if ($sent || $throttleOnFailure) {
            DB::table('low_stock_notification_states')
                ->where('product_id', $req->productId)
                ->update([
                    'last_notified_at' => $nowStr,
                    'last_notified_available_qty' => $available,
                    'updated_at' => $nowStr,
                ]);
        }
    }
}
