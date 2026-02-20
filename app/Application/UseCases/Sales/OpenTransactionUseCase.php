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

        $this->tx->run(function () use ($req, $now) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            if ((string) $t->status !== 'DRAFT') {
                throw new \InvalidArgumentException('only DRAFT can be opened');
            }

            DB::table('transactions')->where('id', $req->transactionId)->update([
                'status' => 'OPEN',
                'payment_status' => 'UNPAID',
                'payment_method' => null,
                'opened_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]);
        });
    }
}
