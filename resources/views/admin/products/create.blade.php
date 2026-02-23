@extends('shared.layouts.app')

@section('title', 'Tambah Produk')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Tambah Produk</h3>
            <p class="text-muted mb-0">Input master produk baru.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Kembali</a>
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

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ url('/admin/products') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">SKU</label>
                        <input class="form-control" type="text" name="sku" value="{{ old('sku') }}" autocomplete="off">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Nama</label>
                        <input class="form-control" type="text" name="name" value="{{ old('name') }}" autocomplete="off">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Harga Jual (angka bulat)</label>
                        <input class="form-control" type="number" name="sell_price_current" value="{{ old('sell_price_current', '0') }}" min="0" step="1">
                        <div class="form-text">Simpan sebagai angka bulat (rupiah).</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Ambang Stok Minimum</label>
                        <input class="form-control" type="number" name="min_stock_threshold" value="{{ old('min_stock_threshold', '3') }}" min="0" step="1">
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Simpan</button>
                        <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection