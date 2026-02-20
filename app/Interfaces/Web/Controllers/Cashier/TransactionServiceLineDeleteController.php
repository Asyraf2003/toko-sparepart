<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\Ports\Services\ClockPort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class TransactionServiceLineDeleteController
{
    public function __construct(private ClockPort $clock) {}

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
            DB::transaction(function () use ($transactionId, $lineId) {
                $tx = DB::table('transactions')->where('id', $transactionId)->lockForUpdate()->first();
                if ($tx === null) {
                    throw new \InvalidArgumentException('transaction not found');
                }

                $status = (string) $tx->status;

                if ($status === 'COMPLETED') {
                    $today = $this->clock->todayBusinessDate();
                    if ((string) $tx->business_date !== $today) {
                        throw new \InvalidArgumentException('cannot delete service on completed transaction except same-day');
                    }
                } elseif (! in_array($status, ['DRAFT', 'OPEN'], true)) {
                    throw new \InvalidArgumentException('transaction not editable');
                }

                $exists = DB::table('transaction_service_lines')->where('id', $lineId)->where('transaction_id', $transactionId)->exists();
                if (! $exists) {
                    throw new \InvalidArgumentException('service line not found');
                }

                DB::table('transaction_service_lines')->where('id', $lineId)->delete();
            });
        } catch (Throwable $e) {
            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
        }

        return redirect('/cashier/transactions/'.$transactionId);
    }
}
