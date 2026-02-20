<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

final readonly class TransactionWorkOrderController
{
    public function __invoke(int $transactionId): View
    {
        $user = request()->user();
        abort_unless($user !== null, 401);

        $tx = DB::table('transactions')->where('id', $transactionId)->first();
        abort_unless($tx !== null, 404);

        if ((string) $tx->status !== 'OPEN') {
            abort(400, 'Work order hanya untuk transaksi OPEN.');
        }

        $parts = DB::table('transaction_part_lines')
            ->join('products', 'products.id', '=', 'transaction_part_lines.product_id')
            ->where('transaction_part_lines.transaction_id', $transactionId)
            ->orderBy('transaction_part_lines.id')
            ->get([
                'transaction_part_lines.id',
                'products.sku',
                'products.name',
                'transaction_part_lines.qty',
            ]);

        $services = DB::table('transaction_service_lines')
            ->where('transaction_id', $transactionId)
            ->orderBy('id')
            ->get([
                'id',
                'description',
            ]);

        return view('cashier.transactions.work_order', [
            'tx' => $tx,
            'parts' => $parts,
            'services' => $services,
        ]);
    }
}
