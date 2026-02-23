@extends('shared.layouts.app')

@section('title', 'Tambah Pembelian')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Tambah Pembelian (Supplier)</h3>
            <p class="text-muted mb-0">Input header dan minimal 1 baris pembelian.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">Kembali</a>
            <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Produk & Stok</a>
        </div>
    </div>
@endsection

@section('content')
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

    <form method="post" action="{{ url('/admin/purchases') }}">
        @csrf

        <div class="row g-3">
            {{-- Header --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Header</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nama Supplier</label>
                                <input class="form-control" type="text" name="supplier_name" value="{{ old('supplier_name') }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">No Faktur (unik)</label>
                                <input class="form-control" type="text" name="no_faktur" value="{{ old('no_faktur') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Tgl Kirim</label>
                                <input class="form-control" type="date" name="tgl_kirim" value="{{ old('tgl_kirim') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Kepada (opsional)</label>
                                <input class="form-control" type="text" name="kepada" value="{{ old('kepada') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">No Pesanan (opsional)</label>
                                <input class="form-control" type="text" name="no_pesanan" value="{{ old('no_pesanan') }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Nama Sales (opsional)</label>
                                <input class="form-control" type="text" name="nama_sales" value="{{ old('nama_sales') }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Total Pajak (rupiah integer, level header)</label>
                                <input class="form-control" type="number" name="total_pajak" min="0" step="1"
                                       value="{{ old('total_pajak', '0') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Catatan (opsional)</label>
                                <input class="form-control" type="text" name="note" value="{{ old('note') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lines --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Baris (isi minimal 1)</div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="min-width: 320px;">Produk</th>
                                    <th style="width: 120px;" class="text-end">Qty</th>
                                    <th style="width: 180px;" class="text-end">Harga Satuan</th>
                                    <th style="width: 160px;" class="text-end">Diskon (%)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @for ($i = 0; $i < 10; $i++)
                                    <tr>
                                        <td>
                                            <select class="form-select" name="lines[{{ $i }}][product_id]">
                                                <option value="">-- pilih --</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->productId }}"
                                                        {{ (string) old("lines.$i.product_id") === (string) $p->productId ? 'selected' : '' }}>
                                                        {{ $p->sku }} - {{ $p->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input class="form-control text-end" type="number" name="lines[{{ $i }}][qty]" min="1" step="1"
                                                   value="{{ old("lines.$i.qty") }}">
                                        </td>
                                        <td>
                                            <input class="form-control text-end" type="number" name="lines[{{ $i }}][unit_cost]" min="0" step="1"
                                                   value="{{ old("lines.$i.unit_cost") }}">
                                        </td>
                                        <td>
                                            <input class="form-control text-end" type="number" name="lines[{{ $i }}][disc_percent]" min="0" max="100" step="0.01"
                                                   value="{{ old("lines.$i.disc_percent", '0') }}">
                                        </td>
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit">Simpan Pembelian</button>
                            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection