<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\ProductRepositoryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class CompleteTransactionUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ProductRepositoryPort $products,
    ) {}

    public function handle(CompleteTransactionRequest $req): void
    {
        if (! in_array($req->paymentMethod, ['CASH', 'TRANSFER'], true)) {
            throw new \InvalidArgumentException('invalid payment method');
        }

        $now = $this->clock->now();
        $today = $this->clock->todayBusinessDate();

        $this->tx->run(function () use ($req, $now, $today) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;
            $businessDate = (string) $t->business_date;

            if (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('transaction not completable');
            }

            // enforce: cashier should only complete same business_date (policy layer nanti middleware, tapi aman di usecase)
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

            // Process sparepart lines: SALE_OUT + RELEASE + freeze COGS
            $lines = DB::table('transaction_part_lines')
                ->where('transaction_id', $req->transactionId)
                ->lockForUpdate()
                ->get(['id', 'product_id', 'qty']);

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

                // update inventory
                DB::table('inventory_stocks')->where('product_id', $productId)->update([
                    'on_hand_qty' => $onHand - $qty,
                    'reserved_qty' => $reserved - $qty,
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ]);

                // ledger SALE_OUT (-qty)
                DB::table('stock_ledgers')->insert([
                    'product_id' => $productId,
                    'type' => 'SALE_OUT',
                    'qty_delta' => -$qty,
                    'ref_type' => 'transaction',
                    'ref_id' => $req->transactionId,
                    'actor_user_id' => $req->actorUserId,
                    'occurred_at' => $now->format('Y-m-d H:i:s'),
                    'note' => 'complete transaction sale out',
                    'created_at' => $now->format('Y-m-d H:i:s'),
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ]);

                // ledger RELEASE (-qty)
                DB::table('stock_ledgers')->insert([
                    'product_id' => $productId,
                    'type' => 'RELEASE',
                    'qty_delta' => -$qty,
                    'ref_type' => 'transaction',
                    'ref_id' => $req->transactionId,
                    'actor_user_id' => $req->actorUserId,
                    'occurred_at' => $now->format('Y-m-d H:i:s'),
                    'note' => 'complete transaction release reserve',
                    'created_at' => $now->format('Y-m-d H:i:s'),
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ]);

                // freeze COGS
                $avgCost = $this->products->getAvgCost($productId);

                DB::table('transaction_part_lines')->where('id', (int) $line->id)->update([
                    'unit_cogs_frozen' => $avgCost,
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ]);
            }

            // update transaction
            DB::table('transactions')->where('id', $req->transactionId)->update([
                'status' => 'COMPLETED',
                'payment_status' => 'PAID',
                'payment_method' => $req->paymentMethod,
                'rounding_mode' => 'NEAREST_1000',
                'rounding_amount' => $roundingAmount,
                'completed_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]);
        });
    }
}
