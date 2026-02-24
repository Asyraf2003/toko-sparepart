<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class CreatePurchaseInvoiceUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private ?NotifyLowStockForProductUseCase $lowStock = null,
    ) {}

    public function handle(CreatePurchaseInvoiceRequest $req): void
    {
        $this->validateRequest($req);

        $now = $this->clock->now();
        $nowStr = $now->format('Y-m-d H:i:s');

        $tglKirim = CarbonImmutable::parse($req->tglKirim);
        $dueDate = $tglKirim->addMonthNoOverflow()->toDateString();

        /** @var list<int> $productIdsToNotify */
        $productIdsToNotify = [];

        $this->tx->run(function () use ($req, $nowStr, $dueDate, &$productIdsToNotify) {
            $productIds = [];
            foreach ($req->lines as $l) {
                $productIds[] = $l->productId;
            }
            $productIds = array_values(array_unique($productIds));

            $existing = DB::table('products')->whereIn('id', $productIds)->count();
            if ($existing !== count($productIds)) {
                throw new \InvalidArgumentException('one or more products not found');
            }

            $computed = $this->computeLinesAndTotals($req);

            $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
                'supplier_name' => $req->supplierName,
                'no_faktur' => $req->noFaktur,
                'tgl_kirim' => $req->tglKirim,
                'due_date' => $dueDate,
                'payment_status' => 'UNPAID',
                'kepada' => $req->kepada,
                'no_pesanan' => $req->noPesanan,
                'nama_sales' => $req->namaSales,
                'total_bruto' => $computed['total_bruto'],
                'total_diskon' => $computed['total_diskon'],
                'total_pajak' => $computed['total_pajak'],
                'grand_total' => $computed['grand_total'],
                'created_by_user_id' => $req->actorUserId,
                'note' => $req->note,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);

            /** @var array<int,array{qty:int,cost:int}> $perProduct */
            $perProduct = [];

            foreach ($computed['lines'] as $row) {
                /** @var CreatePurchaseInvoiceLine $line */
                $line = $row['line'];

                $lineId = (int) DB::table('purchase_invoice_lines')->insertGetId([
                    'purchase_invoice_id' => $invoiceId,
                    'product_id' => $line->productId,
                    'qty' => $line->qty,
                    'unit_cost' => $line->unitCost,
                    'disc_bps' => $line->discBps,
                    'line_total' => $row['line_total'],
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);

                DB::table('stock_ledgers')->insert([
                    'product_id' => $line->productId,
                    'type' => 'PURCHASE_IN',
                    'qty_delta' => +$line->qty,
                    'ref_type' => 'purchase_invoice_line',
                    'ref_id' => $lineId,
                    'actor_user_id' => $req->actorUserId,
                    'occurred_at' => $nowStr,
                    'note' => 'purchase invoice stock in',
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);

                $totalCostForAvg = (int) ($row['line_total'] + $row['allocated_tax']);

                if (! isset($perProduct[$line->productId])) {
                    $perProduct[$line->productId] = ['qty' => 0, 'cost' => 0];
                }
                $perProduct[$line->productId]['qty'] += $line->qty;
                $perProduct[$line->productId]['cost'] += $totalCostForAvg;
            }

            foreach ($perProduct as $productId => $agg) {
                $qtyIn = (int) $agg['qty'];
                $costIn = (int) $agg['cost'];

                if ($qtyIn <= 0) {
                    continue;
                }

                $stock = DB::table('inventory_stocks')
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($stock === null) {
                    throw new \InvalidArgumentException('inventory stock not found for product');
                }

                $product = DB::table('products')
                    ->where('id', $productId)
                    ->lockForUpdate()
                    ->first(['id', 'avg_cost']);

                if ($product === null) {
                    throw new \InvalidArgumentException('product not found');
                }

                $oldOnHand = (int) $stock->on_hand_qty;
                $oldAvgCost = (int) $product->avg_cost;

                $newOnHand = $oldOnHand + $qtyIn;
                if ($newOnHand <= 0) {
                    throw new \InvalidArgumentException('invalid on hand calculation');
                }

                $numerator = ($oldOnHand * $oldAvgCost) + $costIn;
                $newAvgCost = $this->divRoundHalfUp($numerator, $newOnHand);

                DB::table('inventory_stocks')->where('product_id', $productId)->update([
                    'on_hand_qty' => $newOnHand,
                    'updated_at' => $nowStr,
                ]);

                DB::table('products')->where('id', $productId)->update([
                    'avg_cost' => $newAvgCost,
                    'updated_at' => $nowStr,
                ]);

                $productIdsToNotify[] = (int) $productId;
            }
        });

        if ($this->lowStock === null) {
            return;
        }

        $productIdsToNotify = array_values(array_unique($productIdsToNotify));
        foreach ($productIdsToNotify as $pid) {
            $this->lowStock->handle(new NotifyLowStockForProductRequest(
                productId: $pid,
                triggerType: 'PURCHASE_IN',
                actorUserId: $req->actorUserId,
            ));
        }
    }

    // --- methods below unchanged ---
    private function validateRequest(CreatePurchaseInvoiceRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }
        if (trim($req->supplierName) === '') {
            throw new \InvalidArgumentException('supplier name required');
        }
        if (trim($req->noFaktur) === '') {
            throw new \InvalidArgumentException('no_faktur required');
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $req->tglKirim);
        if ($dt === false || $dt->format('Y-m-d') !== $req->tglKirim) {
            throw new \InvalidArgumentException('invalid tgl_kirim format (expected Y-m-d)');
        }

        if ($req->totalPajak < 0) {
            throw new \InvalidArgumentException('total_pajak cannot be negative');
        }

        if (count($req->lines) === 0) {
            throw new \InvalidArgumentException('lines required');
        }

        foreach ($req->lines as $line) {
            if (! $line instanceof CreatePurchaseInvoiceLine) {
                throw new \InvalidArgumentException('invalid line payload');
            }
            if ($line->productId <= 0) {
                throw new \InvalidArgumentException('invalid product id');
            }
            if ($line->qty <= 0) {
                throw new \InvalidArgumentException('qty must be > 0');
            }
            if ($line->unitCost < 0) {
                throw new \InvalidArgumentException('unit_cost cannot be negative');
            }
            if ($line->discBps < 0 || $line->discBps > 10000) {
                throw new \InvalidArgumentException('disc_bps must be within 0..10000');
            }
        }
    }

    /**
     * @return array{
     *   total_bruto:int,
     *   total_diskon:int,
     *   total_pajak:int,
     *   grand_total:int,
     *   lines:list<array{line:CreatePurchaseInvoiceLine, line_total:int, allocated_tax:int}>
     * }
     */
    private function computeLinesAndTotals(CreatePurchaseInvoiceRequest $req): array
    {
        $totalBruto = 0;
        $totalDiskon = 0;

        $lineTotals = [];
        $lineBrutos = [];
        $lineDiskons = [];

        foreach ($req->lines as $idx => $line) {
            $bruto = $line->qty * $line->unitCost;
            $diskon = $this->divRoundHalfUp($bruto * $line->discBps, 10000);

            $net = $bruto - $diskon;
            if ($net < 0) {
                throw new \InvalidArgumentException('line net total cannot be negative');
            }

            $totalBruto += $bruto;
            $totalDiskon += $diskon;

            $lineBrutos[$idx] = $bruto;
            $lineDiskons[$idx] = $diskon;
            $lineTotals[$idx] = $net;
        }

        $sumNet = array_sum($lineTotals);
        $totalPajak = (int) $req->totalPajak;

        if ($sumNet === 0 && $totalPajak > 0) {
            throw new \InvalidArgumentException('cannot allocate header tax when sum line net is zero');
        }

        $allocated = array_fill(0, count($lineTotals), 0);
        $remainders = [];

        $allocatedSum = 0;
        if ($totalPajak > 0 && $sumNet > 0) {
            foreach ($lineTotals as $i => $net) {
                $numerator = $totalPajak * $net;
                $floor = intdiv($numerator, $sumNet);
                $rem = $numerator % $sumNet;

                $allocated[$i] = $floor;
                $remainders[$i] = $rem;
                $allocatedSum += $floor;
            }

            $remaining = $totalPajak - $allocatedSum;
            if ($remaining > 0) {
                $indices = array_keys($remainders);
                usort($indices, function (int $a, int $b) use ($remainders): int {
                    $ra = $remainders[$a];
                    $rb = $remainders[$b];
                    if ($ra === $rb) {
                        return $a <=> $b;
                    }

                    return $rb <=> $ra;
                });

                for ($k = 0; $k < $remaining; $k++) {
                    $allocated[$indices[$k % count($indices)]] += 1;
                }
            }
        }

        $grandTotal = $sumNet + $totalPajak;

        $outLines = [];
        foreach ($req->lines as $idx => $line) {
            $outLines[] = [
                'line' => $line,
                'line_total' => (int) $lineTotals[$idx],
                'allocated_tax' => (int) $allocated[$idx],
            ];
        }

        return [
            'total_bruto' => (int) $totalBruto,
            'total_diskon' => (int) $totalDiskon,
            'total_pajak' => (int) $totalPajak,
            'grand_total' => (int) $grandTotal,
            'lines' => $outLines,
        ];
    }

    private function divRoundHalfUp(int $numerator, int $denominator): int
    {
        if ($denominator <= 0) {
            throw new \InvalidArgumentException('invalid denominator');
        }
        if ($numerator <= 0) {
            return 0;
        }

        $half = intdiv($denominator, 2);

        return intdiv($numerator + $half, $denominator);
    }
}