<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\Ports\Services\ClockPort;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class TransactionServiceLineUpdateController
{
    public function __construct(private ClockPort $clock) {}

    public function __invoke(int $transactionId, int $lineId): RedirectResponse
    {
        $user = request()->user();
        if ($user === null) {
            return redirect('/login');
        }

        $data = request()->validate([
            'description' => ['required', 'string', 'min:1', 'max:255'],
            'price_manual' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($transactionId, $lineId, $data) {
                $tx = DB::table('transactions')->where('id', $transactionId)->lockForUpdate()->first();
                if ($tx === null) {
                    throw new \InvalidArgumentException('transaction not found');
                }

                $status = (string) $tx->status;

                // service boleh edit di DRAFT/OPEN, dan COMPLETED hanya same-day
                if ($status === 'COMPLETED') {
                    $today = $this->clock->todayBusinessDate();
                    if ((string) $tx->business_date !== $today) {
                        throw new \InvalidArgumentException('cannot edit service on completed transaction except same-day');
                    }
                } elseif (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                    throw new \InvalidArgumentException('transaction not editable');
                }

                $line = DB::table('transaction_service_lines')->where('id', $lineId)->where('transaction_id', $transactionId)->lockForUpdate()->first();
                if ($line === null) {
                    throw new \InvalidArgumentException('service line not found');
                }

                $now = (new DateTimeImmutable)->format('Y-m-d H:i:s');

                DB::table('transaction_service_lines')->where('id', $lineId)->update([
                    'description' => (string) $data['description'],
                    'price_manual' => (int) $data['price_manual'],
                    'updated_at' => $now,
                ]);
            });
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
