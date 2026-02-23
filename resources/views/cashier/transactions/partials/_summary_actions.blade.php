@php
    $status = (string) ($tx->status ?? '');
    $paymentStatus = (string) ($tx->payment_status ?? '');

    $isPaid = ($status === 'COMPLETED') || ($paymentStatus === 'PAID');
    $canVoid = ($status !== 'VOID');
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h4>Aksi Nota</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div>Total Sparepart: <x-ui.rupiah :value="$partsTotal" /></div>
            <div>Total Servis: <x-ui.rupiah :value="$serviceTotal" /></div>
            <div>Total Akhir: <x-ui.rupiah :value="$grossTotal" /></div>
            <div class="d-none">
                Total Tunai Setelah Pembulatan: <x-ui.rupiah :value="$roundedCashTotal" />
                (pembulatan: <x-ui.rupiah :value="$cashRoundingAmount" />)
            </div>
        </div>

        @if ($status !== 'VOID')
            @if ($isPaid)
                <div class="alert alert-light">
                    Transaksi sudah selesai dibayar. Aksi pembayaran dinonaktifkan.
                </div>
            @else
                <div class="d-flex gap-2 flex-wrap flex-md-nowrap align-items-stretch">

                    {{-- SIMPAN / OPEN: hanya untuk DRAFT --}}
                    @if ($status === 'DRAFT')
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
                    @endif

                    {{-- COMPLETE: hanya untuk OPEN --}}
                    @if ($status === 'OPEN')
                        <form method="post"
                              action="{{ url('/cashier/transactions/'.$tx->id.'/complete-cash') }}"
                              class="flex-fill m-0"
                              id="form_complete_cash_calc">
                            @csrf
                            <input type="hidden" name="cash_received" id="cash_received_hidden" value="0">

                            <button type="submit"
                                    id="btn_complete_cash_calc"
                                    class="btn icon icon-left btn-success w-100 h-100 d-flex align-items-center justify-content-center"
                                    disabled>
                                <i data-feather="dollar-sign"></i>
                                <span>Selesaikan Tunai</span>
                            </button>
                        </form>

                        <form method="post"
                              action="{{ url('/cashier/transactions/'.$tx->id.'/complete-transfer') }}"
                              class="flex-fill m-0">
                            @csrf
                            <button type="submit"
                                    class="btn icon icon-left btn-info w-100 h-100 d-flex align-items-center justify-content-center">
                                <i data-feather="credit-card"></i>
                                <span>Selesaikan Transfer</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- VOID: tetap tampil untuk DRAFT/OPEN/COMPLETED, selama belum VOID --}}
            @if ($canVoid)
                <form method="post"
                      action="{{ url('/cashier/transactions/'.$tx->id.'/void') }}"
                      class="mt-3 row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label class="form-label">Alasan VOID</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-danger">VOID</button>
                    </div>
                </form>
            @endif
        @endif

        @if ($status === 'OPEN')
            <div class="mt-3">
                <a class="btn btn-light"
                   href="{{ url('/cashier/transactions/'.$tx->id.'/work-order') }}"
                   target="_blank" rel="noopener">
                    Cetak Work Order
                </a>
            </div>
        @endif
    </div>
</div>