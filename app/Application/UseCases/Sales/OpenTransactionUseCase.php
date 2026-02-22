<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class OpenTransactionUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
    ) {}

    public function handle(OpenTransactionRequest $req): void
    {
        $now = $this->clock->now();
        $nowStr = $now->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $nowStr) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            $status = (string) $t->status;

            // DRAFT: boleh di-open (behavior lama)
            // OPEN: boleh patch data pembeli (tanpa ubah payment fields)
            // COMPLETED/VOID: tidak boleh
            if (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('only DRAFT/OPEN can be updated');
            }

            $update = [];

            if ($status === 'DRAFT') {
                $update = [
                    'status' => 'OPEN',
                    'payment_status' => 'UNPAID',
                    'payment_method' => null,
                    'opened_at' => $nowStr,
                ];
            }

            // Patch customer fields (hanya yang dikirim)
            $allowed = ['customer_name', 'customer_phone', 'vehicle_plate', 'note'];
            foreach ($req->fields as $k => $v) {
                if (! is_string($k)) {
                    continue;
                }
                if (! in_array($k, $allowed, true)) {
                    continue;
                }
                $update[$k] = $v; // bisa null (misal user kosongkan)
            }

            // Kalau tidak ada perubahan apapun, tetap aman (no-op).
            // Tapi biasanya ada setidaknya updated_at.
            if ($update === []) {
                return;
            }

            $update['updated_at'] = $nowStr;

            DB::table('transactions')->where('id', $req->transactionId)->update($update);
        });
    }
}
