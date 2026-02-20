<?php

declare(strict_types=1);

namespace App\Application\UseCases\Sales;

use App\Application\Ports\Repositories\TransactionServiceLineRepositoryPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class AddServiceLineUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private TransactionServiceLineRepositoryPort $serviceLines,
    ) {}

    public function handle(AddServiceLineRequest $req): int
    {
        $desc = trim($req->description);

        if ($desc === '') {
            throw new \InvalidArgumentException('description is required');
        }

        if ($req->priceManual < 0) {
            throw new \InvalidArgumentException('priceManual must be >= 0');
        }

        return $this->tx->run(function () use ($req, $desc) {
            $t = DB::table('transactions')->where('id', $req->transactionId)->lockForUpdate()->first();
            if ($t === null) {
                throw new \InvalidArgumentException('transaction not found');
            }

            if (! in_array($t->status, ['DRAFT', 'OPEN'], true)) {
                throw new \InvalidArgumentException('transaction not editable');
            }

            return $this->serviceLines->createLine(
                transactionId: $req->transactionId,
                description: $desc,
                priceManual: $req->priceManual,
            );
        });
    }
}
