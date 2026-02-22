<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use Illuminate\Support\Facades\DB;

final readonly class CompleteTransactionUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ProductRepositoryPort $products,
        private NotifyLowStockForProductUseCase $lowStock,
    ) {}

    public function handle(CompleteTransactionRequest $req): void
    {
        if (! in_array($req->paymentMethod, ['CASH', 'TRANSFER'], true)) {
            throw new \InvalidArgumentException('invalid payment method');
        }

        $now = $this->clock->now();
        $today = $this->clock->todayBusinessDate();

        /** @var list<int> $productIdsToNotify */
        $productIdsToNotify = [];

        $this->tx->run(function () use ($req, $now, $today, &$productIdsToNotify) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            $businessDate = (string) $t->business_date;

            if (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('transaction not completable');
            }

            if ($businessDate !== $today) {
                throw new \InvalidArgumentException('cannot complete transaction for different business date');
            }

            $partsTotal = (int) DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->sum('line_subtotal');

            $serviceTotal = (int) DB::table('transaction_service_lines')
                ->where('transaction_id', $req->transactionId)
                ->sum('price_manual');

            $grossTotal = $partsTotal + $serviceTotal;

            $roundingAmount = 0;

            if ($req->paymentMethod === 'CASH') {
                $rounded = (int) (round($grossTotal / 1000, 0, PHP_ROUND_HALF_UP) * 1000);
                $roundingAmount = $rounded - $grossTotal;
            }

            $requiredCashTotal = $grossTotal + $roundingAmount;

            // cash fields (enterprise)
            $cashReceived = null;
            $cashChange = null;

            if ($req->paymentMethod === 'CASH') {
                // null => quick cash uang pas
                $cashReceived = $req->cashReceived ?? $requiredCashTotal;

                if ($cashReceived < $requiredCashTotal) {
                    throw new \InvalidArgumentException('cash received insufficient');
                }

                $cashChange = $cashReceived - $requiredCashTotal;
            }

            $lines = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->get(['id', 'product_id', 'qty']);

            $nowStr = $now->format('Y-m-d H:i:s');

            foreach ($lines as $line) {
                $productId = (int) $line->product_id;
                $qty = (int) $line->qty;

                if ($qty <= 0) {
                    continue;
                }

                $stock = DB::table('inventory_stocks')
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($stock === null) {
                    throw new \InvalidArgumentException('inventory stock not found');
                }

                $onHand = (int) $stock->on_hand_qty;
                $reserved = (int) $stock->reserved_qty;

                if ($reserved < $qty) {
                    throw new \InvalidArgumentException('reserved stock insufficient at completion');
                }
                if ($onHand < $qty) {
                    throw new \InvalidArgumentException('on hand stock insufficient at completion');
                }

                DB::table('inventory_stocks')->where('product_id', $productId)->update([
                    'on_hand_qty' => $onHand - $qty,
                    'reserved_qty' => $reserved - $qty,
                    'updated_at' => $nowStr,
                ]);

                DB::table('stock_ledgers')->insert([
                    'product_id' => $productId,
                    'type' => 'SALE_OUT',
                    'qty_delta' => -$qty,
                    'ref_type' => 'transaction',
                    'ref_id' => $req->transactionId,
                    'actor_user_id' => $req->actorUserId,
                    'occurred_at' => $nowStr,
                    'note' => 'complete transaction sale out',
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);

                DB::table('stock_ledgers')->insert([
                    'product_id' => $productId,
                    'type' => 'RELEASE',
                    'qty_delta' => -$qty,
                    'ref_type' => 'transaction',
                    'ref_id' => $req->transactionId,
                    'actor_user_id' => $req->actorUserId,
                    'occurred_at' => $nowStr,
                    'note' => 'complete transaction release reserve',
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);

                $avgCost = $this->products->getAvgCost($productId);

                DB::table('transaction_part_lines')->where('id', (int) $line->id)->update([
                    'unit_cogs_frozen' => $avgCost,
                    'updated_at' => $nowStr,
                ]);

                $productIdsToNotify[] = $productId;
            }

            DB::table('transactions')->where('id', $req->transactionId)->update([
                'status' => 'COMPLETED',
                'payment_status' => 'PAID',
                'payment_method' => $req->paymentMethod,
                'rounding_mode' => 'NEAREST_1000',
                'rounding_amount' => $roundingAmount,

                // enterprise cash fields
                'cash_received' => $cashReceived, // null untuk TRANSFER
                'cash_change' => $cashChange,     // null untuk TRANSFER

                'completed_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);
        });

        $productIdsToNotify = array_values(array_unique($productIdsToNotify));
        foreach ($productIdsToNotify as $pid) {
            $this->lowStock->handle(new NotifyLowStockForProductRequest(
                productId: $pid,
                triggerType: 'SALE_OUT',
                actorUserId: $req->actorUserId,
            ));
        }
    }
}