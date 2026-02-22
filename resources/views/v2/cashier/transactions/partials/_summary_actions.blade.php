<div class="card mt-3">
    <div class="card-header">
        <h4>Aksi Nota</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div>Total Sparepart: {{ $partsTotal }}</div>
            <div>Total Service: {{ $serviceTotal }}</div>
            <div>Grand Total: {{ $grossTotal }}</div>
            <div class="d-none">Cash Rounded Total: {{ $roundedCashTotal }} (rounding: {{ $cashRoundingAmount }})</div>
        </div>

        @if ($tx->status !== 'VOID')
            <div class="d-flex gap-2 flex-wrap flex-md-nowrap align-items-stretch">

                {{-- SIMPAN --}}
                <form method="post"
                    action="{{ url('/cashier/transactions/'.$tx->id.'/open') }}"
                    class="flex-fill m-0">
                    @csrf
                    <button type="submit"
                            class="btn icon icon-left btn-light w-100 h-100 d-flex align-items-center justify-content-center">
                        <i data-feather="save"></i>
                        <span>Simpan Nota</span>
                    </button>
                </form>

                {{-- COMPLETE CASH --}}
                <form method="post"
                    action="{{ url('/cashier/transactions/'.$tx->id.'/complete-cash') }}"
                    class="flex-fill m-0">
                    @csrf
                    <button type="submit"
                            class="btn icon icon-left btn-success w-100 h-100 d-flex align-items-center justify-content-center">
                        <i data-feather="dollar-sign"></i>
                        <span>Complete Cash</span>
                    </button>
                </form>

                {{-- COMPLETE TRANSFER --}}
                <form method="post"
                    action="{{ url('/cashier/transactions/'.$tx->id.'/complete-transfer') }}"
                    class="flex-fill m-0">
                    @csrf
                    <button type="submit"
                            class="btn icon icon-left btn-info w-100 h-100 d-flex align-items-center justify-content-center">
                        <i data-feather="credit-card"></i>
                        <span>Complete Transfer</span>
                    </button>
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