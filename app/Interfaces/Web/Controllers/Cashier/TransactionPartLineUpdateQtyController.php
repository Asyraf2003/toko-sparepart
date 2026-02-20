<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class TransactionPartLineUpdateQtyController
{
    public function __invoke(int $transactionId, int $lineId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'qty' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($transactionId, $lineId, $user, $data) {
                $tx = DB::table('transactions')->where('id', $transactionId)->lockForUpdate()->first();
                if ($tx === null) {
                    throw new \InvalidArgumentException('transaction not found');
                }

                $status = (string) $tx->status;
                if (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                    throw new \InvalidArgumentException('cannot edit part lines unless DRAFT/OPEN');
                }

                $line = DB::table('transaction_part_lines')->where('id', $lineId)->where('transaction_id', $transactionId)->lockForUpdate()->first();
                if ($line === null) {
                    throw new \InvalidArgumentException('part line not found');
                }

                $oldQty = (int) $line->qty;
                $newQty = (int) $data['qty'];
                $delta = $newQty - $oldQty;

                if ($delta === 0) {
                    return;
                }

                $productId = (int) $line->product_id;

                $stock = DB::table('inventory_stocks')->where('product_id', $productId)->lockForUpdate()->first();
                if ($stock === null) {
                    throw new \InvalidArgumentException('inventory stock not found');
                }

                $onHand = (int) $stock->on_hand_qty;
                $reserved = (int) $stock->reserved_qty;
                $available = $onHand - $reserved;

                $now = (new DateTimeImmutable)->format('Y-m-d H:i:s');

                if ($delta > 0) {
                    if ($available < $delta) {
                        throw new \InvalidArgumentException('insufficient available stock');
                    }

                    DB::table('inventory_stocks')->where('product_id', $productId)->update([
                        'reserved_qty' => $reserved + $delta,
                        'updated_at' => $now,
                    ]);

                    DB::table('stock_ledgers')->insert([
                        'product_id' => $productId,
                        'type' => 'RESERVE',
                        'qty_delta' => $delta,
                        'ref_type' => 'transaction',
                        'ref_id' => $transactionId,
                        'actor_user_id' => (int) $user->id,
                        'occurred_at' => $now,
                        'note' => 'update part qty: '.$data['reason'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $releaseQty = abs($delta);

                    if ($reserved < $releaseQty) {
                        throw new \InvalidArgumentException('reserved stock insufficient');
                    }

                    DB::table('inventory_stocks')->where('product_id', $productId)->update([
                        'reserved_qty' => $reserved - $releaseQty,
                        'updated_at' => $now,
                    ]);

                    DB::table('stock_ledgers')->insert([
                        'product_id' => $productId,
                        'type' => 'RELEASE',
                        'qty_delta' => -$releaseQty,
                        'ref_type' => 'transaction',
                        'ref_id' => $transactionId,
                        'actor_user_id' => (int) $user->id,
                        'occurred_at' => $now,
                        'note' => 'update part qty: '.$data['reason'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $unitPrice = (int) $line->unit_sell_price_frozen;
                DB::table('transaction_part_lines')->where('id', $lineId)->update([
                    'qty' => $newQty,
                    'line_subtotal' => $unitPrice * $newQty,
                    'updated_at' => $now,
                ]);
            });
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
