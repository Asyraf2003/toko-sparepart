@extends('shared.layouts.app')

@section('title', 'Edit Pembelian')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Edit Pembelian (Header)</h3>
            <p class="text-muted mb-0">Invoice #{{ $invoice->id }} · {{ $invoice->no_faktur }}</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases/'.$invoice->id) }}">← Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="alert alert-warning">
        <div class="fw-bold">Catatan Kebijakan</div>
        <div>Edit hanya untuk <span class="fw-semibold">header</span> (metadata). Baris (qty/harga_satuan/diskon) tidak diedit agar stok & avg_cost tetap konsisten.</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Terjadi kesalahan validasi</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ url('/admin/purchases/'.$invoice->id) }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nama Supplier</label>
                        <input class="form-control" type="text" name="supplier_name" value="{{ old('supplier_name', $invoice->supplier_name) }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">No Faktur (unik)</label>
                        <input class="form-control" type="text" name="no_faktur" value="{{ old('no_faktur', $invoice->no_faktur) }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Tgl Kirim</label>
                        <input class="form-control" type="date" name="tgl_kirim" value="{{ old('tgl_kirim', $invoice->tgl_kirim) }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Kepada (opsional)</label>
                        <input class="form-control" type="text" name="kepada" value="{{ old('kepada', $invoice->kepada) }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">No Pesanan (opsional)</label>
                        <input class="form-control" type="text" name="no_pesanan" value="{{ old('no_pesanan', $invoice->no_pesanan) }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Nama Sales (opsional)</label>
                        <input class="form-control" type="text" name="nama_sales" value="{{ old('nama_sales', $invoice->nama_sales) }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Catatan (opsional)</label>
                        <input class="form-control" type="text" name="note" value="{{ old('note', $invoice->note) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Alasan Perubahan (wajib)</label>
                        <input class="form-control" type="text" name="reason" value="{{ old('reason') }}" required>
                        <div class="form-text">Dicatat ke audit log.</div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                        <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases/'.$invoice->id) }}">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection