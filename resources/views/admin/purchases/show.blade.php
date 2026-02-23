@extends('shared.layouts.app')

@section('title', 'Detail Pembelian')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Detail Pembelian</h3>
            <p class="text-muted mb-0">
                Faktur: <span class="fw-semibold">{{ $invoice->no_faktur }}</span> · Supplier: {{ $invoice->supplier_name }}
            </p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ url('/admin/purchases/'.$invoice->id.'/edit') }}">Edit Header</a>
            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">← Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = function ($v): string {
            if ($v === null) return '-';
            if (is_numeric($v)) return number_format((float) $v, 0, ',', '.');
            return (string) $v;
        };
        $createdBy = $invoice->created_by_name ?: ('User#'.$invoice->created_by_user_id);
    @endphp

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Header</div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width:180px;">Tgl Kirim</th><td>{{ $invoice->tgl_kirim }}</td></tr>
                            <tr><th>No Faktur</th><td>{{ $invoice->no_faktur }}</td></tr>
                            <tr><th>Supplier</th><td>{{ $invoice->supplier_name }}</td></tr>
                            <tr><th>Kepada</th><td>{{ $invoice->kepada ?? '-' }}</td></tr>
                            <tr><th>No Pesanan</th><td>{{ $invoice->no_pesanan ?? '-' }}</td></tr>
                            <tr><th>Nama Sales</th><td>{{ $invoice->nama_sales ?? '-' }}</td></tr>
                            <tr><th>Catatan</th><td>{{ $invoice->note ?? '-' }}</td></tr>
                            <tr><th>Dibuat</th><td>{{ $invoice->created_at }} · {{ $createdBy }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">Total</div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width:180px;">Bruto</th><td class="text-end">{{ $fmt($invoice->total_bruto) }}</td></tr>
                            <tr><th>Diskon</th><td class="text-end">{{ $fmt($invoice->total_diskon) }}</td></tr>
                            <tr><th>Pajak</th><td class="text-end">{{ $fmt($invoice->total_pajak) }}</td></tr>
                            <tr><th>Total Akhir</th><td class="text-end fw-semibold">{{ $fmt($invoice->grand_total) }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Baris</div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="width:90px;">SKU</th>
                                <th>Nama</th>
                                <th class="text-end" style="width:90px;">Qty</th>
                                <th class="text-end" style="width:150px;">Harga Satuan</th>
                                <th class="text-end" style="width:120px;">Diskon (%)</th>
                                <th class="text-end" style="width:160px;">Total Baris</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($lines as $l)
                                @php
                                    $discPct = number_format(((int) $l->disc_bps) / 100, 2, ',', '.');
                                @endphp
                                <tr>
                                    <td>{{ $l->sku }}</td>
                                    <td>{{ $l->name }}</td>
                                    <td class="text-end">{{ $l->qty }}</td>
                                    <td class="text-end">{{ $fmt($l->unit_cost) }}</td>
                                    <td class="text-end">{{ $discPct }}</td>
                                    <td class="text-end">{{ $fmt($l->line_total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">Tidak ada baris</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted mt-2">
                        Catatan: pajak disimpan di header (total_pajak). Alokasi pajak per-baris tidak disimpan.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection