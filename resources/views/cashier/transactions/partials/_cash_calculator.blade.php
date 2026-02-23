@php
    $status = (string) ($tx->status ?? '');
    $paymentStatus = (string) ($tx->payment_status ?? '');
    $paymentMethod = (string) ($tx->payment_method ?? '');

    $isPaid = ($status === 'COMPLETED') || ($paymentStatus === 'PAID');
    $isPayable = (!$isPaid) && ($status === 'OPEN');

    $cashReceived = $tx->cash_received ?? null;
    $cashChange = $tx->cash_change ?? null;

    $netCash = null;
    if ($cashReceived !== null && $cashChange !== null) {
        $netCash = (int) $cashReceived - (int) $cashChange;
    }
@endphp

<div class="card mt-3" id="cash_calc_root" data-rounded-total="{{ (int) ($roundedCashTotal ?? 0) }}">
    <div class="card-header">
        <h4>Pembayaran</h4>
    </div>

    <div class="card-body">
        @if ($isPaid)
            <div class="mb-2">
                <div>Status: <b>{{ $tx->payment_status ?? '-' }}</b></div>
                <div>Metode: <b>{{ $tx->payment_method ?? '-' }}</b></div>
            </div>

            <div>
                <div>Total: <b><x-ui.rupiah :value="$roundedCashTotal" /></b></div>
                <div>Pembulatan: <x-ui.rupiah :value="$cashRoundingAmount" /></div>
            </div>

            @if ($paymentMethod === 'CASH')
                <hr>
                <div>Tunai diterima: <b><x-ui.rupiah :value="$cashReceived" /></b></div>
                <div>Kembalian: <b><x-ui.rupiah :value="$cashChange" /></b></div>
                <div>Tunai bersih: <b><x-ui.rupiah :value="$netCash" /></b></div>
            @endif

            <div class="mt-2 text-muted" style="font-size: 12px;">
                Pembayaran sudah selesai. Kalkulator dinonaktifkan.
            </div>

        @elseif (! $isPayable)
            <div class="mb-2">
                <div>Status Transaksi: <b>{{ $tx->status ?? '-' }}</b></div>
            </div>

            <div>
                <div>Total Akhir: <b><x-ui.rupiah :value="$grossTotal" /></b></div>
                <div>
                    Total Tunai Setelah Pembulatan: <b><x-ui.rupiah :value="$roundedCashTotal" /></b>
                    (pembulatan: <x-ui.rupiah :value="$cashRoundingAmount" />)
                </div>
            </div>

            <div class="mt-2 text-muted" style="font-size: 12px;">
                Pembayaran hanya tersedia saat nota <b>OPEN</b>. Silakan klik <b>Simpan Nota</b> terlebih dahulu.
            </div>

        @else
            <div>
                <div>Total Akhir: <b><x-ui.rupiah :value="$grossTotal" /></b></div>
                <div>
                    Total Tunai Setelah Pembulatan: <b><x-ui.rupiah :value="$roundedCashTotal" /></b>
                    (pembulatan: <x-ui.rupiah :value="$cashRoundingAmount" />)
                </div>
            </div>

            <div class="mt-3" style="max-width: 260px;">
                <label class="form-label">Tunai diterima</label>
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