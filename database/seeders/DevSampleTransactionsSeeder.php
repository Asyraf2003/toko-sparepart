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
            throw new \RuntimeException('No users found. Run DefaultUsersSeeder first.');
        }
        $actorUserId = (int) $user->id;

        $productIds = DB::table('products')->orderBy('id')->pluck('id')->all();
        if (count($productIds) === 0) {
            throw new \RuntimeException('No products found. Run DevSampleProductsSeeder first.');
        }

        // Ensure inventory rows exist for all products (schema contract).
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

        // Range baru: awal Des 2025 s/d akhir Feb 2026
        $start = CarbonImmutable::parse('2025-12-01');
        $end = CarbonImmutable::parse('2026-02-28');

        for ($d = $start; $d->lte($end); $d = $d->addDay()) {
            $businessDate = $d->toDateString();

            // Target 50–75 "orang" (transaksi) per hari
            $dailyTarget = random_int(50, 75);

            // Komposisi status harian (mayoritas COMPLETED), lalu disesuaikan agar total tepat = dailyTarget
            $completedCount = (int) round($dailyTarget * (random_int(70, 85) / 100));
            $openCount = (int) round($dailyTarget * (random_int(5, 10) / 100));
            $draftCount = (int) round($dailyTarget * (random_int(5, 10) / 100));
            $voidOpenCount = (int) round($dailyTarget * (random_int(2, 5) / 100));
            $voidCompletedCount = (int) round($dailyTarget * (random_int(2, 5) / 100));

            $sum = $completedCount + $openCount + $draftCount + $voidOpenCount + $voidCompletedCount;
            if ($sum < $dailyTarget) {
                $completedCount += ($dailyTarget - $sum);
            } elseif ($sum > $dailyTarget) {
                $over = $sum - $dailyTarget;
                $completedCount = max(0, $completedCount - $over);
            }

            // Reset sequence per hari (TRX-YYYYMMDD-0001 dst)
            $seq = 1;

            // COMPLETED
            for ($i = 0; $i < $completedCount; $i++) {
                $openedAt = $this->randomOccurredAt($d, 9, 18);
                $openedAtStr = $openedAt->toDateTimeString();

                $txId = $this->createOpenTransaction($businessDate, $actorUserId, $seq++, $openedAtStr);

                $this->addPartAndServiceLines(
                    transactionId: $txId,
                    productIds: $productIds,
                    actorUserId: $actorUserId,
                    occurredAt: $openedAtStr,
                    partLineCount: random_int(1, 3),
                    serviceLineCount: random_int(0, 2),
                    doReserve: true,
                );

                $completedAt = $openedAt->addMinutes(random_int(15, 180));
                $this->completeTransaction(
                    transactionId: $txId,
                    actorUserId: $actorUserId,
                    occurredAt: $completedAt->toDateTimeString(),
                    paymentMethod: random_int(0, 1) === 1 ? 'CASH' : 'TRANSFER',
                );
            }

            // OPEN
            for ($i = 0; $i < $openCount; $i++) {
                $openedAt = $this->randomOccurredAt($d, 9, 18);
                $openedAtStr = $openedAt->toDateTimeString();

                $txOpenId = $this->createOpenTransaction($businessDate, $actorUserId, $seq++, $openedAtStr);
                $this->addPartAndServiceLines(
                    transactionId: $txOpenId,
                    productIds: $productIds,
                    actorUserId: $actorUserId,
                    occurredAt: $openedAtStr,
                    partLineCount: random_int(1, 2),
                    serviceLineCount: random_int(0, 1),
                    doReserve: true,
                );
            }

            // DRAFT
            for ($i = 0; $i < $draftCount; $i++) {
                $createdAt = $this->randomOccurredAt($d, 9, 18)->toDateTimeString();
                $this->createDraftTransaction($businessDate, $actorUserId, $seq++, $createdAt);
            }

            // VOID from OPEN (release reserved)
            for ($i = 0; $i < $voidOpenCount; $i++) {
                $openedAt = $this->randomOccurredAt($d, 9, 18);
                $openedAtStr = $openedAt->toDateTimeString();

                $txVoidOpenId = $this->createOpenTransaction($businessDate, $actorUserId, $seq++, $openedAtStr);
                $this->addPartAndServiceLines(
                    transactionId: $txVoidOpenId,
                    productIds: $productIds,
                    actorUserId: $actorUserId,
                    occurredAt: $openedAtStr,
                    partLineCount: random_int(1, 2),
                    serviceLineCount: 0,
                    doReserve: true,
                );

                $voidedAt = $openedAt->addMinutes(random_int(5, 120));
                $this->voidOpenOrDraftTransaction(
                    transactionId: $txVoidOpenId,
                    actorUserId: $actorUserId,
                    occurredAt: $voidedAt->toDateTimeString(),
                );
            }

            // VOID from COMPLETED (VOID_IN on_hand)
            for ($i = 0; $i < $voidCompletedCount; $i++) {
                $openedAt = $this->randomOccurredAt($d, 9, 18);
                $openedAtStr = $openedAt->toDateTimeString();

                $txVoidCompletedId = $this->createOpenTransaction($businessDate, $actorUserId, $seq++, $openedAtStr);
                $this->addPartAndServiceLines(
                    transactionId: $txVoidCompletedId,
                    productIds: $productIds,
                    actorUserId: $actorUserId,
                    occurredAt: $openedAtStr,
                    partLineCount: random_int(1, 2),
                    serviceLineCount: random_int(0, 1),
                    doReserve: true,
                );

                $completedAt = $openedAt->addMinutes(random_int(15, 180));
                $this->completeTransaction(
                    transactionId: $txVoidCompletedId,
                    actorUserId: $actorUserId,
                    occurredAt: $completedAt->toDateTimeString(),
                    paymentMethod: 'CASH',
                );

                $voidedAt = $completedAt->addMinutes(random_int(5, 90));
                $this->voidCompletedTransaction(
                    transactionId: $txVoidCompletedId,
                    actorUserId: $actorUserId,
                    occurredAt: $voidedAt->toDateTimeString(),
                );
            }
        }

        // Opsional: jaga-jaga untuk UI kasir (cashier gate hanya today).
        // Jika "hari ini" di luar range seed, buat minimal 3 transaksi today.
        $today = CarbonImmutable::now()->toDateString();
        if ($today < $start->toDateString() || $today > $end->toDateString()) {
            $t10 = CarbonImmutable::now()->setTime(10, 0)->toDateTimeString();
            $seqToday = 1;

            // 1x OPEN hari ini
            $txTodayOpen = $this->createOpenTransaction($today, $actorUserId, $seqToday++, $t10);
            $this->addPartAndServiceLines(
                transactionId: $txTodayOpen,
                productIds: $productIds,
                actorUserId: $actorUserId,
                occurredAt: $t10,
                partLineCount: 2,
                serviceLineCount: 1,
                doReserve: true,
            );

            // 1x COMPLETED CASH hari ini
            $txTodayCompleted = $this->createOpenTransaction($today, $actorUserId, $seqToday++, $t10);
            $this->addPartAndServiceLines(
                transactionId: $txTodayCompleted,
                productIds: $productIds,
                actorUserId: $actorUserId,
                occurredAt: $t10,
                partLineCount: 2,
                serviceLineCount: 1,
                doReserve: true,
            );
            $this->completeTransaction(
                transactionId: $txTodayCompleted,
                actorUserId: $actorUserId,
                occurredAt: CarbonImmutable::now()->setTime(12, 0)->toDateTimeString(),
                paymentMethod: 'CASH',
            );

            // 1x DRAFT hari ini
            $this->createDraftTransaction($today, $actorUserId, $seqToday++, $t10);
        }
    }

    private function randomOccurredAt(CarbonImmutable $day, int $fromHour, int $toHour): CarbonImmutable
    {
        if ($toHour <= $fromHour) {
            $toHour = $fromHour + 1;
        }

        $start = $day->setTime($fromHour, 0);
        $minutesWindow = (($toHour - $fromHour) * 60) - 1;
        $randMin = random_int(0, max(0, $minutesWindow));

        return $start->addMinutes($randMin);
    }

    private function createDraftTransaction(string $businessDate, int $actorUserId, int $seq, string $createdAt): int
    {
        $txNumber = sprintf('TRX-%s-%04d', str_replace('-', '', $businessDate), $seq);
        [$cn, $cp, $plate] = $this->randomCustomer();

        return (int) DB::table('transactions')->insertGetId([
            'transaction_number' => $txNumber,
            'business_date' => $businessDate,
            'status' => 'DRAFT',
            'payment_status' => 'UNPAID',
            'payment_method' => null,

            'rounding_mode' => null,
            'rounding_amount' => 0,

            'cash_received' => null,
            'cash_change' => null,

            'customer_name' => $cn,
            'customer_phone' => $cp,
            'vehicle_plate' => $plate,

            'service_employee_id' => null,
            'note' => 'DEV seed',

            'opened_at' => null,
            'completed_at' => null,
            'voided_at' => null,

            'created_by_user_id' => $actorUserId,

            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createOpenTransaction(string $businessDate, int $actorUserId, int $seq, string $openedAt): int
    {
        $txNumber = sprintf('TRX-%s-%04d', str_replace('-', '', $businessDate), $seq);
        [$cn, $cp, $plate] = $this->randomCustomer();

        return (int) DB::table('transactions')->insertGetId([
            'transaction_number' => $txNumber,
            'business_date' => $businessDate,
            'status' => 'OPEN',
            'payment_status' => 'UNPAID',
            'payment_method' => null,

            'rounding_mode' => null,
            'rounding_amount' => 0,

            'cash_received' => null,
            'cash_change' => null,

            'customer_name' => $cn,
            'customer_phone' => $cp,
            'vehicle_plate' => $plate,

            'service_employee_id' => null,
            'note' => 'DEV seed',

            'opened_at' => $openedAt,
            'completed_at' => null,
            'voided_at' => null,

            'created_by_user_id' => $actorUserId,

            'created_at' => $openedAt,
            'updated_at' => $openedAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
            $stock = (object) ['on_hand_qty' => 0, 'reserved_qty' => 0];
        }

        $onHand = (int) $stock->on_hand_qty;
        $reserved = (int) $stock->reserved_qty;
        $available = $onHand - $reserved;

        if ($available < $qty) {
            $need = ($qty - $available) + 5;
            $this->adjustOnHand($productId, $need, $actorUserId, $occurredAt, 'DEV seed top-up for reserve');
        }

        DB::table('inventory_stocks')->where('product_id', $productId)->update([
            'reserved_qty' => DB::raw('reserved_qty + '.(int) $qty),
            'updated_at' => $occurredAt,
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
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }

    private function completeTransaction(int $transactionId, int $actorUserId, string $occurredAt, string $paymentMethod): void
    {
        // Freeze COGS & apply SALE_OUT + RELEASE per stock contract.
        $lines = DB::table('transaction_part_lines')
            ->where('transaction_id', $transactionId)
            ->get(['id', 'product_id', 'qty', 'line_subtotal']);

        foreach ($lines as $l) {
            $productId = (int) $l->product_id;
            $qty = (int) $l->qty;

            $product = DB::table('products')->where('id', $productId)->first(['avg_cost']);
            $avgCost = $product !== null ? (int) $product->avg_cost : 0;

            DB::table('transaction_part_lines')->where('id', (int) $l->id)->update([
                'unit_cogs_frozen' => $avgCost,
                'updated_at' => $occurredAt,
            ]);

            if (! DB::table('inventory_stocks')->where('product_id', $productId)->exists()) {
                DB::table('inventory_stocks')->insert([
                    'product_id' => $productId,
                    'on_hand_qty' => 0,
                    'reserved_qty' => 0,
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ]);
            }

            $stock = DB::table('inventory_stocks')->where('product_id', $productId)->first(['on_hand_qty', 'reserved_qty']);
            $onHand = (int) ($stock->on_hand_qty ?? 0);

            if ($onHand < $qty) {
                $this->adjustOnHand($productId, ($qty - $onHand) + 5, $actorUserId, $occurredAt, 'DEV seed top-up for sale_out');
            }

            DB::table('inventory_stocks')->where('product_id', $productId)->update([
                'on_hand_qty' => DB::raw('on_hand_qty - '.(int) $qty),
                'reserved_qty' => DB::raw('reserved_qty - '.(int) $qty),
                'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }

        // --- enterprise cash fields (calculator) ---
        $partsTotal = (int) DB::table('transaction_part_lines')
            ->where('transaction_id', $transactionId)
            ->sum('line_subtotal');

        $serviceTotal = (int) DB::table('transaction_service_lines')
            ->where('transaction_id', $transactionId)
            ->sum('price_manual');

        $grossTotal = $partsTotal + $serviceTotal;

        $roundingAmount = 0;
        $cashReceived = null;
        $cashChange = null;

        if ($paymentMethod === 'CASH') {
            $rounded = (int) (round($grossTotal / 1000, 0, PHP_ROUND_HALF_UP) * 1000);
            $roundingAmount = $rounded - $grossTotal;
            $required = $grossTotal + $roundingAmount;

            // Simulasikan kalkulator: kadang uang pas, kadang lebih
            $extraChoices = [0, 1000, 2000, 5000, 10000, 20000];
            $extra = (int) $extraChoices[array_rand($extraChoices)];
            $cashReceived = $required + $extra;
            $cashChange = $cashReceived - $required;
        }

        DB::table('transactions')->where('id', $transactionId)->update([
            'status' => 'COMPLETED',
            'payment_status' => 'PAID',
            'payment_method' => $paymentMethod,

            'rounding_mode' => 'NEAREST_1000',
            'rounding_amount' => $roundingAmount,

            'cash_received' => $cashReceived,
            'cash_change' => $cashChange,

            'completed_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }

    private function voidOpenOrDraftTransaction(int $transactionId, int $actorUserId, string $occurredAt): void
    {
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
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ]);
            }

            DB::table('inventory_stocks')->where('product_id', $productId)->update([
                'reserved_qty' => DB::raw('reserved_qty - '.(int) $qty),
                'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }

        DB::table('transactions')->where('id', $transactionId)->update([
            'status' => 'VOID',
            'voided_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }

    private function voidCompletedTransaction(int $transactionId, int $actorUserId, string $occurredAt): void
    {
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
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ]);
            }

            DB::table('inventory_stocks')->where('product_id', $productId)->update([
                'on_hand_qty' => DB::raw('on_hand_qty + '.(int) $qty),
                'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }

        DB::table('transactions')->where('id', $transactionId)->update([
            'status' => 'VOID',
            'voided_at' => $occurredAt,
            'updated_at' => $occurredAt,
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
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }

        DB::table('inventory_stocks')->where('product_id', $productId)->update([
            'on_hand_qty' => DB::raw('on_hand_qty + '.(int) $qtyDelta),
            'updated_at' => $occurredAt,
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
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function randomCustomer(): array
    {
        $names = ['Andi', 'Budi', 'Citra', 'Deni', 'Eka', 'Fikri', 'Gilang', 'Hani', 'Indra', 'Joko'];
        $name = $names[array_rand($names)];

        $phone = '08'.(string) random_int(1111111111, 9999999999);
        $plate = 'DD '.(string) random_int(1000, 9999).' '.Str::upper(Str::random(2));

        return [$name, $phone, $plate];
    }
}