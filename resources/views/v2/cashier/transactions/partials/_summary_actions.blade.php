<div class="card mt-3">
    <div class="card-header">
        <h4>Aksi Nota</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div>Total Sparepart: {{ $partsTotal }}</div>
            <div>Total Service: {{ $serviceTotal }}</div>
            <div>Grand Total: {{ $grossTotal }}</div>
            <div>Cash Rounded Total: {{ $roundedCashTotal }} (rounding: {{ $cashRoundingAmount }})</div>
        </div>

        @if ($tx->status !== 'VOID')
            <div class="d-flex gap-2 flex-wrap">
                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/open') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">SIMPAN OPEN (UNPAID)</button>
                </form>

                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/complete-cash') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">COMPLETE CASH</button>
                </form>

                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/complete-transfer') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">COMPLETE TRANSFER</button>
                </form>
            </div>

            <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/void') }}" class="mt-3 row g-2 align-items-end">
                @csrf
                <div class="col-12 col-md-5">
                    <label class="form-label">Reason VOID</label>
                    <input type="text" name="reason" class="form-control" required>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-danger">VOID</button>
                </div>
            </form>
        @endif

        @if (($tx->status ?? '') === 'OPEN')
            <div class="mt-3">
                <a class="btn btn-light"
                   href="{{ url('/cashier/transactions/'.$tx->id.'/work-order') }}"
                   target="_blank" rel="noopener">
                    Print Work Order
                </a>
            </div>
        @endif
    </div>
</div>