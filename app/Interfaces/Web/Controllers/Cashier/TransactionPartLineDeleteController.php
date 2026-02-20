<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class TransactionPartLineDeleteController
{
    public function __invoke(int $transactionId, int $lineId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
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
                    throw new \InvalidArgumentException('cannot delete part lines unless DRAFT/OPEN');
                }

                $line = DB::table('transaction_part_lines')->where('id', $lineId)->where('transaction_id', $transactionId)->lockForUpdate()->first();
                if ($line === null) {
                    throw new \InvalidArgumentException('part line not found');
                }

                $productId = (int) $line->product_id;
                $qty = (int) $line->qty;

                $stock = DB::table('inventory_stocks')->where('product_id', $productId)->lockForUpdate()->first();
                if ($stock === null) {
                    throw new \InvalidArgumentException('inventory stock not found');
                }

                $reserved = (int) $stock->reserved_qty;
                if ($reserved < $qty) {
                    throw new \InvalidArgumentException('reserved stock insufficient');
                }

                $now = (new DateTimeImmutable)->format('Y-m-d H:i:s');

                DB::table('inventory_stocks')->where('product_id', $productId)->update([
                    'reserved_qty' => $reserved - $qty,
                    'updated_at' => $now,
                ]);

                DB::table('stock_ledgers')->insert([
                    'product_id' => $productId,
                    'type' => 'RELEASE',
                    'qty_delta' => -$qty,
                    'ref_type' => 'transaction',
                    'ref_id' => $transactionId,
                    'actor_user_id' => (int) $user->id,
                    'occurred_at' => $now,
                    'note' => 'delete part line: '.$data['reason'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('transaction_part_lines')->where('id', $lineId)->delete();
            });
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
