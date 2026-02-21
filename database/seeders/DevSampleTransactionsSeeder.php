<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DevSampleTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('transactions')->count() > 0) {
            return;
        }

        $user = DB::table('users')->orderBy('id')->first();
        if ($user === null) {
            // Zero-assumption fallback: require existing DefaultUsersSeeder.
            throw new \RuntimeException('No users found. Run DefaultUsersSeeder first.');
        }
        $actorUserId = (int) $user->id;

        $productIds = DB::table('products')->orderBy('id')->pluck('id')->all();
        if (count($productIds) === 0) {
            throw new \RuntimeException('No products found. Run DevSampleProductsSeeder first.');
        }

        // Ensure inventory rows exist for all products (schema contract from ADR-0003).
        foreach ($productIds as $pid) {
            $pid = (int) $pid;
            if (! DB::table('inventory_stocks')->where('product_id', $pid)->exists()) {
                DB::table('inventory_stocks')->insert([
                    'product_id' => $pid,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $start = CarbonImmutable::parse('2026-02-10');
        $end = CarbonImmutable::parse('2026-02-21');

        $txSeq = 1;

        for ($d = $start; $d->lte($end); $d = $d->addDay()) {
            $businessDate = $d->toDateString();
            $occurredAt = $d->setTime(10, 0)->toDateTimeString();

            // 2x COMPLETED (via OPEN -> reserve -> complete)
            for ($i = 0; $i < 2; $i++) {
                $txId = $this->createOpenTransaction($businessDate, $actorUserId, $txSeq++, $occurredAt);

                $lineCount = random_int(1, 3);
                $this->addPartAndServiceLines(
                    transactionId: $txId,
                    productIds: $productIds,
                    actorUserId: $actorUserId,
                    occurredAt: $occurredAt,
                    partLineCount: $lineCount,
                    serviceLineCount: random_int(0, 2),
                    doReserve: true,
                );

                $this->completeTransaction(
                    transactionId: $txId,
                    actorUserId: $actorUserId,
                    occurredAt: $d->setTime(12, 0)->toDateTimeString(),
                    paymentMethod: random_int(0, 1) === 1 ? 'CASH' : 'TRANSFER',
                );
            }

            // 1x OPEN (reserved exists)
            $txOpenId = $this->createOpenTransaction($businessDate, $actorUserId, $txSeq++, $occurredAt);
            $this->addPartAndServiceLines(
                transactionId: $txOpenId,
                productIds: $productIds,
                actorUserId: $actorUserId,
                occurredAt: $occurredAt,
                partLineCount: random_int(1, 2),
                serviceLineCount: random_int(0, 1),
                doReserve: true,
            );

            // 1x VOID (void from OPEN => RELEASE reserved)
            $txVoidOpenId = $this->createOpenTransaction($businessDate, $actorUserId, $txSeq++, $occurredAt);
            $this->addPartAndServiceLines(
                transactionId: $txVoidOpenId,
                productIds: $productIds,
                actorUserId: $actorUserId,
                occurredAt: $occurredAt,
                partLineCount: random_int(1, 2),
                serviceLineCount: 0,
                doReserve: true,
            );
            $this->voidOpenOrDraftTransaction(
                transactionId: $txVoidOpenId,
                actorUserId: $actorUserId,
                occurredAt: $d->setTime(14, 0)->toDateTimeString(),
            );

            // 1x VOID (void from COMPLETED => VOID_IN on_hand)
            $txVoidCompletedId = $this->createOpenTransaction($businessDate, $actorUserId, $txSeq++, $occurredAt);
            $this->addPartAndServiceLines(
                transactionId: $txVoidCompletedId,
                productIds: $productIds,
                actorUserId: $actorUserId,
                occurredAt: $occurredAt,
                partLineCount: random_int(1, 2),
                serviceLineCount: random_int(0, 1),
                doReserve: true,
            );
            $this->completeTransaction(
                transactionId: $txVoidCompletedId,
                actorUserId: $actorUserId,
                occurredAt: $d->setTime(15, 0)->toDateTimeString(),
                paymentMethod: 'CASH',
            );
            $this->voidCompletedTransaction(
                transactionId: $txVoidCompletedId,
                actorUserId: $actorUserId,
                occurredAt: $d->setTime(16, 0)->toDateTimeString(),
            );
        }
    }

    private function createOpenTransaction(string $businessDate, int $actorUserId, int $seq, string $openedAt): int
    {
        $txNumber = sprintf('TRX-%s-%04d', str_replace('-', '', $businessDate), $seq);

        return (int) DB::table('transactions')->insertGetId([
            'transaction_number' => $txNumber,
            'business_date' => $businessDate,
            'status' => 'OPEN',
            'payment_status' => 'UNPAID',
            'payment_method' => null,

            'rounding_mode' => null,
            'rounding_amount' => 0,

            'customer_name' => null,
            'customer_phone' => null,
            'vehicle_plate' => null,

            'service_employee_id' => null,
            'note' => 'DEV seed',

            'opened_at' => $openedAt,
            'completed_at' => null,
            'voided_at' => null,

            'created_by_user_id' => $actorUserId,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addPartAndServiceLines(
        int $transactionId,
        array $productIds,
        int $actorUserId,
        string $occurredAt,
        int $partLineCount,
        int $serviceLineCount,
        bool $doReserve,
    ): void {
        // Part lines
        for ($i = 0; $i < $partLineCount; $i++) {
            $productId = (int) $productIds[array_rand($productIds)];
            $qty = random_int(1, 3);

            $product = DB::table('products')->where('id', $productId)->first(['sell_price_current', 'avg_cost']);
            if ($product === null) {
                continue;
            }

            $unitSell = (int) $product->sell_price_current;
            $lineSubtotal = $unitSell * $qty;

            DB::table('transaction_part_lines')->insert([
                'transaction_id' => $transactionId,
                'product_id' => $productId,
                'qty' => $qty,
                'unit_sell_price_frozen' => $unitSell,
                'line_subtotal' => $lineSubtotal,
                'unit_cogs_frozen' => null, // will be frozen on completion per contract
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($doReserve) {
                $this->reserveStockForTransactionPart($productId, $qty, $transactionId, $actorUserId, $occurredAt);
            }
        }

        // Service lines
        for ($i = 0; $i < $serviceLineCount; $i++) {
            DB::table('transaction_service_lines')->insert([
                'transaction_id' => $transactionId,
                'description' => 'Service '.Str::upper(Str::random(4)),
                'price_manual' => random_int(20000, 75000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function reserveStockForTransactionPart(int $productId, int $qty, int $transactionId, int $actorUserId, string $occurredAt): void
    {
        $stock = DB::table('inventory_stocks')->where('product_id', $productId)->first(['on_hand_qty', 'reserved_qty']);
        if ($stock === null) {
            DB::table('inventory_stocks')->insert([
                'product_id' => $productId,
                'on_hand_qty' => 0,
                'reserved_qty' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $stock = (object) ['on_hand_qty' => 0, 'reserved_qty' => 0];
        }

        $onHand = (int) $stock->on_hand_qty;
        $reserved = (int) $stock->reserved_qty;
        $available = $onHand - $reserved;

        // Dev seed must keep invariant available >= 0 and allow reserve:
        if ($available < $qty) {
            $need = ($qty - $available) + 5; // small buffer
            $this->adjustOnHand($productId, $need, $actorUserId, $occurredAt, 'DEV seed top-up for reserve');
        }

        DB::table('inventory_stocks')->where('product_id', $productId)->update([
            'reserved_qty' => DB::raw('reserved_qty + '.(int) $qty),
            'updated_at' => now(),
        ]);

        DB::table('stock_ledgers')->insert([
            'product_id' => $productId,
            'type' => 'RESERVE',
            'qty_delta' => (int) $qty,
            'ref_type' => 'transaction',
            'ref_id' => (int) $transactionId,
            'actor_user_id' => $actorUserId,
            'occurred_at' => $occurredAt,
            'note' => 'DEV seed reserve',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function completeTransaction(int $transactionId, int $actorUserId, string $occurredAt, string $paymentMethod): void
    {
        // Freeze COGS & apply SALE_OUT + RELEASE per stock contract.
        $lines = DB::table('transaction_part_lines')
            ->where('transaction_id', $transactionId)
            ->get(['id', 'product_id', 'qty']);

        foreach ($lines as $l) {
            $productId = (int) $l->product_id;
            $qty = (int) $l->qty;

            $product = DB::table('products')->where('id', $productId)->first(['avg_cost']);
            $avgCost = $product !== null ? (int) $product->avg_cost : 0;

            // Freeze unit_cogs_frozen on completion
            DB::table('transaction_part_lines')->where('id', (int) $l->id)->update([
                'unit_cogs_frozen' => $avgCost,
                'updated_at' => now(),
            ]);

            // Ensure inventory row exists
            if (! DB::table('inventory_stocks')->where('product_id', $productId)->exists()) {
                DB::table('inventory_stocks')->insert([
                    'product_id' => $productId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Ensure on_hand is enough (anti-minus). If not, top-up via ADJUSTMENT.
            $stock = DB::table('inventory_stocks')->where('product_id', $productId)->first(['on_hand_qty', 'reserved_qty']);
            $onHand = (int) ($stock->on_hand_qty ?? 0);

            if ($onHand < $qty) {
                $this->adjustOnHand($productId, ($qty - $onHand) + 5, $actorUserId, $occurredAt, 'DEV seed top-up for sale_out');
            }

            // on_hand -= qty, reserved -= qty (reserved may be >= qty because we reserved on add line)
            DB::table('inventory_stocks')->where('product_id', $productId)->update([
                'on_hand_qty' => DB::raw('on_hand_qty - '.(int) $qty),
                'reserved_qty' => DB::raw('reserved_qty - '.(int) $qty),
                'updated_at' => now(),
            ]);

            DB::table('stock_ledgers')->insert([
                'product_id' => $productId,
                'type' => 'SALE_OUT',
                'qty_delta' => -1 * (int) $qty,
                'ref_type' => 'transaction',
                'ref_id' => (int) $transactionId,
                'actor_user_id' => $actorUserId,
                'occurred_at' => $occurredAt,
                'note' => 'DEV seed sale out',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('stock_ledgers')->insert([
                'product_id' => $productId,
                'type' => 'RELEASE',
                'qty_delta' => -1 * (int) $qty,
                'ref_type' => 'transaction',
                'ref_id' => (int) $transactionId,
                'actor_user_id' => $actorUserId,
                'occurred_at' => $occurredAt,
                'note' => 'DEV seed release on completion',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('transactions')->where('id', $transactionId)->update([
            'status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'payment_method' => $paymentMethod,
            'completed_at' => $occurredAt,
            'updated_at' => now(),
        ]);
    }

    private function voidOpenOrDraftTransaction(int $transactionId, int $actorUserId, string $occurredAt): void
    {
        // VOID OPEN/DRAFT: reserved -= qty; ledger RELEASE (-qty)
        $lines = DB::table('transaction_part_lines')
            ->where('transaction_id', $transactionId)
            ->get(['product_id', 'qty']);

        foreach ($lines as $l) {
            $productId = (int) $l->product_id;
            $qty = (int) $l->qty;

            if (! DB::table('inventory_stocks')->where('product_id', $productId)->exists()) {
                DB::table('inventory_stocks')->insert([
                    'product_id' => $productId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('inventory_stocks')->where('product_id', $productId)->update([
                'reserved_qty' => DB::raw('reserved_qty - '.(int) $qty),
                'updated_at' => now(),
            ]);

            DB::table('stock_ledgers')->insert([
                'product_id' => $productId,
                'type' => 'RELEASE',
                'qty_delta' => -1 * (int) $qty,
                'ref_type' => 'transaction',
                'ref_id' => (int) $transactionId,
                'actor_user_id' => $actorUserId,
                'occurred_at' => $occurredAt,
                'note' => 'DEV seed release on void open/draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('transactions')->where('id', $transactionId)->update([
            'status' => 'VOID',
            'voided_at' => $occurredAt,
            'updated_at' => now(),
        ]);
    }

    private function voidCompletedTransaction(int $transactionId, int $actorUserId, string $occurredAt): void
    {
        // VOID COMPLETED: on_hand += qty; ledger VOID_IN (+qty)
        $lines = DB::table('transaction_part_lines')
            ->where('transaction_id', $transactionId)
            ->get(['product_id', 'qty']);

        foreach ($lines as $l) {
            $productId = (int) $l->product_id;
            $qty = (int) $l->qty;

            if (! DB::table('inventory_stocks')->where('product_id', $productId)->exists()) {
                DB::table('inventory_stocks')->insert([
                    'product_id' => $productId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('inventory_stocks')->where('product_id', $productId)->update([
                'on_hand_qty' => DB::raw('on_hand_qty + '.(int) $qty),
                'updated_at' => now(),
            ]);

            DB::table('stock_ledgers')->insert([
                'product_id' => $productId,
                'type' => 'VOID_IN',
                'qty_delta' => (int) $qty,
                'ref_type' => 'transaction',
                'ref_id' => (int) $transactionId,
                'actor_user_id' => $actorUserId,
                'occurred_at' => $occurredAt,
                'note' => 'DEV seed void completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('transactions')->where('id', $transactionId)->update([
            'status' => 'VOID',
            'voided_at' => $occurredAt,
            'updated_at' => now(),
        ]);
    }

    private function adjustOnHand(int $productId, int $qtyDelta, int $actorUserId, string $occurredAt, string $note): void
    {
        if ($qtyDelta === 0) {
            return;
        }

        if (! DB::table('inventory_stocks')->where('product_id', $productId)->exists()) {
            DB::table('inventory_stocks')->insert([
                'product_id' => $productId,
                'on_hand_qty' => 0,
                'reserved_qty' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('inventory_stocks')->where('product_id', $productId)->update([
            'on_hand_qty' => DB::raw('on_hand_qty + '.(int) $qtyDelta),
            'updated_at' => now(),
        ]);

        DB::table('stock_ledgers')->insert([
            'product_id' => $productId,
            'type' => 'ADJUSTMENT',
            'qty_delta' => (int) $qtyDelta,
            'ref_type' => 'dev_seed',
            'ref_id' => null,
            'actor_user_id' => $actorUserId,
            'occurred_at' => $occurredAt,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
