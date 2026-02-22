@php
    $status = (string) ($tx->status ?? '');
    $canEdit = in_array($status, ['DRAFT', 'OPEN'], true);
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h4>Data Pembeli</h4>
    </div>

    <div class="card-body">
        @if (!$canEdit)
            <div class="text-muted">
                Data pembeli tidak bisa diubah untuk transaksi status: <b>{{ $status }}</b>
            </div>
        @else
            <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/open') }}" class="m-0">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nama Pembeli</label>
                    <input type="text"
                           name="customer_name"
                           class="form-control"
                           value="{{ old('customer_name', $tx->customer_name ?? '') }}"
                           maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">No HP / WA</label>
                    <input type="text"
                           name="customer_phone"
                           class="form-control"
                           value="{{ old('customer_phone', $tx->customer_phone ?? '') }}"
                           maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Plat Kendaraan</label>
                    <input type="text"
                           name="vehicle_plate"
                           class="form-control"
                           value="{{ old('vehicle_plate', $tx->vehicle_plate ?? '') }}"
                           maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="note"
                              class="form-control"
                              rows="3">{{ old('note', $tx->note ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-light">
                    Simpan Data Pembeli
                </button>
            </form>
        @endif
    </div>
</div>