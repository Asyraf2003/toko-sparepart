<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Cashier;

use App\Application\Ports\Services\ClockPort;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class TransactionTodayController
{
    public function __construct(private ClockPort $clock) {}

    public function __invoke(): View|\Illuminate\Http\Response
    {
        $today = $this->clock->todayBusinessDate();

        $status = trim((string) request()->query('status', ''));
        $q = trim((string) request()->query('q', ''));

        $hasCustomerName = Schema::hasColumn('transactions', 'customer_name');
        $hasVehiclePlate = Schema::hasColumn('transactions', 'vehicle_plate');

        $select = [
            'id',
            'transaction_number',
            'status',
            'payment_status',
            'payment_method',
            'rounding_amount',
            'created_at',
            'completed_at',
            'voided_at',
        ];

        if ($hasCustomerName) {
            $select[] = 'customer_name';
        }

        if ($hasVehiclePlate) {
            $select[] = 'vehicle_plate';
        }

        // penting: jangan ikutkan fragment/page ke link pagination
        $appends = request()->except(['page', 'fragment']);

        $rows = DB::table('transactions')
            ->where('business_date', $today)
            ->when($status !== '', fn ($qq) => $qq->where('status', $status))
            ->when($q !== '', fn ($qq) => $qq->where('transaction_number', 'like', '%'.$q.'%'))
            ->orderByDesc('id') // terbaru -> lama
            ->paginate(10, $select)
            ->appends($appends);

        // Fragment response (untuk JS)
        if ((string) request()->query('fragment', '') === '1') {
            return response()->view('cashier.transactions.partials._today_list', [
                'rows' => $rows,
                'hasCustomerName' => $hasCustomerName,
                'hasVehiclePlate' => $hasVehiclePlate,
            ]);
        }

        // Full page
        return view('cashier.transactions.today', [
            'today' => $today,
            'rows' => $rows,
            'status' => $status,
            'q' => $q,
            'hasCustomerName' => $hasCustomerName,
            'hasVehiclePlate' => $hasVehiclePlate,
        ]);
    }
}