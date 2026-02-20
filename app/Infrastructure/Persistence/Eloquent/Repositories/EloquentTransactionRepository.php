<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Sales\TransactionSnapshot;
use App\Application\Ports\Repositories\TransactionRepositoryPort;
use App\Infrastructure\Persistence\Eloquent\Models\Transaction;
use Illuminate\Support\Facades\DB;

final class EloquentTransactionRepository implements TransactionRepositoryPort
{
    public function nextTransactionNumberForDate(string $businessDate): string
    {
        // INV-YYYYMMDD-XXXX
        $ymd = str_replace('-', '', $businessDate);
        $prefix = "INV-{$ymd}-";

        // lock rows for that date to avoid race condition
        $last = DB::table('transactions')
            ->select('transaction_number')
            ->where('transaction_number', 'like', $prefix.'%')
            ->orderBy('transaction_number', 'desc')
            ->lockForUpdate()
            ->value('transaction_number');

        $nextSeq = 1;

        if (is_string($last) && str_starts_with($last, $prefix)) {
            $suffix = substr($last, strlen($prefix)); // "0001"
            $n = (int) $suffix;
            $nextSeq = $n + 1;
        }

        $seq = str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        return $prefix.$seq;
    }

    public function createDraft(string $businessDate, int $createdByUserId, string $transactionNumber): TransactionSnapshot
    {
        $t = Transaction::query()->create([
            'transaction_number' => $transactionNumber,
            'business_date' => $businessDate,
            'status' => 'DRAFT',
            'payment_status' => 'UNPAID',
            'payment_method' => null,
            'rounding_mode' => 'NEAREST_1000',
            'rounding_amount' => 0,
            'created_by_user_id' => $createdByUserId,
        ]);

        return new TransactionSnapshot(
            id: (int) $t->id,
            transactionNumber: (string) $t->transaction_number,
            businessDate: (string) $t->business_date->format('Y-m-d'),
            status: (string) $t->status,
            paymentStatus: (string) $t->payment_status,
            paymentMethod: $t->payment_method === null ? null : (string) $t->payment_method,
            createdByUserId: (int) $t->created_by_user_id,
        );
    }
}
