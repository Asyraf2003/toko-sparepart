@php
    $status = (string) ($tx->status ?? '');
    $paymentStatus = (string) ($tx->payment_status ?? '');
    $paymentMethod = (string) ($tx->payment_method ?? '');

    // Paid/completed => jangan tampil kalkulator input.
    $isPaid = ($status === 'COMPLETED') || ($paymentStatus === 'PAID');

    // Payable hanya saat OPEN dan belum paid.
    $isPayable = (!$isPaid) && ($status === 'OPEN');

    $cashReceived = $tx->cash_received ?? null;
    $cashChange = $tx->cash_change ?? null;

    $netCash = null;
    if ($cashReceived !== null && $cashChange !== null) {
        $netCash = (int) $cashReceived - (int) $cashChange;
    }
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h4>Pembayaran</h4>
    </div>

    <div class="card-body">
        @if ($isPaid)
            {{-- PAID/COMPLETED: ringkasan --}}
            <div class="mb-2">
                <div>Status: <b>{{ $tx->payment_status ?? '-' }}</b></div>
                <div>Metode: <b>{{ $tx->payment_method ?? '-' }}</b></div>
            </div>

            <div>
                <div>Total: <b><x-ui.rupiah :value="$roundedCashTotal" /></b></div>
                <div>Rounding: <x-ui.rupiah :value="$cashRoundingAmount" /></div>
            </div>

            @if ($paymentMethod === 'CASH')
                <hr>
                <div>Cash diterima: <b><x-ui.rupiah :value="$cashReceived" /></b></div>
                <div>Kembalian: <b><x-ui.rupiah :value="$cashChange" /></b></div>
                <div>Net Cash: <b><x-ui.rupiah :value="$netCash" /></b></div>
            @endif

            <div class="mt-2 text-muted" style="font-size: 12px;">
                Pembayaran sudah selesai. Kalkulator dinonaktifkan.
            </div>

        @elseif (! $isPayable)
            {{-- DRAFT (atau state lain selain OPEN): jangan tampil kalkulator --}}
            <div class="mb-2">
                <div>Status Transaksi: <b>{{ $tx->status ?? '-' }}</b></div>
            </div>

            <div>
                <div>Grand Total: <b><x-ui.rupiah :value="$grossTotal" /></b></div>
                <div>
                    Cash Rounded Total: <b><x-ui.rupiah :value="$roundedCashTotal" /></b>
                    (rounding: <x-ui.rupiah :value="$cashRoundingAmount" />)
                </div>
            </div>

            <div class="mt-2 text-muted" style="font-size: 12px;">
                Pembayaran hanya tersedia saat nota <b>OPEN</b>. Silakan klik <b>Simpan Nota</b> terlebih dahulu.
            </div>

        @else
            {{-- OPEN dan belum paid: kalkulator aktif --}}
            <div>
                <div>Grand Total: <b><x-ui.rupiah :value="$grossTotal" /></b></div>
                <div>
                    Cash Rounded Total: <b><x-ui.rupiah :value="$roundedCashTotal" /></b>
                    (rounding: <x-ui.rupiah :value="$cashRoundingAmount" />)
                </div>
            </div>

            <div class="mt-3" style="max-width: 260px;">
                <label class="form-label">Cash diterima</label>
                <input id="cash_received" type="number" min="0" value="0" class="form-control">
            </div>

            <div class="mt-4">
                <div class="text-muted">Kembalian</div>
                <div class="fw-bold" style="font-size: 42px; line-height: 1.1;">
                    <span id="cash_change">0</span>
                </div>
                <div class="text-muted" style="font-size: 12px;">
                    <span id="cash_short_wrap" class="d-none">
                        Kurang: <b><span id="cash_short">0</span></b>
                    </span>
                </div>
            </div>
        @endif
    </div>
</div>

@if ($isPayable)
<script>
(function () {
    var total = {{ (int) ($roundedCashTotal ?? 0) }};
    var input = document.getElementById('cash_received');
    var out = document.getElementById('cash_change');

    var shortWrap = document.getElementById('cash_short_wrap');
    var shortOut = document.getElementById('cash_short');

    function formatRupiahInt(n) {
        n = parseInt(String(n || '0'), 10);
        if (isNaN(n)) n = 0;

        var sign = n < 0 ? '-' : '';
        var s = String(Math.abs(n));
        var outStr = s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return sign + outStr;
    }

    function calc() {
        var received = parseInt(input.value || '0', 10);
        if (isNaN(received)) received = 0;

        var change = received - total;

        if (change < 0) {
            out.textContent = '0';
            if (shortWrap) shortWrap.classList.remove('d-none');
            if (shortOut) shortOut.textContent = formatRupiahInt(Math.abs(change));
        } else {
            out.textContent = formatRupiahInt(change);
            if (shortWrap) shortWrap.classList.add('d-none');
            if (shortOut) shortOut.textContent = '0';
        }

        var hidden = document.getElementById('cash_received_hidden');
        if (hidden) hidden.value = String(received);

        var btn = document.getElementById('btn_complete_cash_calc');
        if (btn) btn.disabled = received < total;
    }

    input.addEventListener('input', calc);
    document.addEventListener('DOMContentLoaded', calc);
})();
</script>
@endif