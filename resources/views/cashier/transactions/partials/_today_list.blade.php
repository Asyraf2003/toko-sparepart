@php
    // rows adalah paginator (LengthAwarePaginator)
    $total = $rows->total();
    $first = $rows->firstItem();
    $last = $rows->lastItem();
@endphp

<div id="today_fragment_root"
     data-total="{{ $total }}"
     data-first="{{ $first ?? '' }}"
     data-last="{{ $last ?? '' }}">

    @if($rows->count() === 0)
        <p class="mb-0">Belum ada transaksi hari ini.</p>
    @else
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted" style="font-size: 12px;">
                @if ($first !== null && $last !== null)
                    Menampilkan {{ $first }}–{{ $last }} dari {{ $total }}
                @endif
            </div>
            <div>
                {{ $rows->links('vendor.pagination.mazer') }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-lg mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>No</th>
                    @if($hasCustomerName)
                        <th>Pelanggan</th>
                    @endif
                    <th>Status</th>
                    <th>Status Bayar</th>
                    <th>Metode</th>
                    <th class="d-none">Pembulatan</th>
                    @if($hasVehiclePlate)
                        <th class="d-none">Plat</th>
                    @endif
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->transaction_number }}</td>
                        @if($hasCustomerName)
                            <td>{{ $r->customer_name ?? '-' }}</td>
                        @endif
                        <td>{{ $r->status }}</td>
                        <td>{{ $r->payment_status }}</td>
                        <td>{{ $r->payment_method ?? '-' }}</td>
                        <td class="d-none">{{ $r->rounding_amount ?? 0 }}</td>
                        @if($hasVehiclePlate)
                            <td class="d-none">{{ $r->vehicle_plate ?? '-' }}</td>
                        @endif
                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ url('/cashier/transactions/'.$r->id) }}">
                                Buka
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>