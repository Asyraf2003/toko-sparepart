@extends('cashier.layouts.app')

@section('title', 'Kasir - Cari Produk')

@section('page_heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">Cari Produk</h3>
            <div class="text-muted">Cari SKU / Nama (minimal 2 karakter)</div>
        </div>

        <div>
            <a href="{{ url('/cashier/dashboard') }}" class="btn btn-light">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $initial = (string) ($q ?? '');
        $initialTrim = trim($initial);
        $canSearchLocal = (bool) ($canSearch ?? (mb_strlen($initialTrim) >= 2));
        $rowsLocal = $rows ?? collect();
    @endphp

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Pencarian</h4>
            </div>

            <div class="card-body">
                <form method="get" action="{{ url('/cashier/products/search') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-10">
                        <label class="form-label">Kata Kunci</label>
                        <input type="text" name="pq" class="form-control" value="{{ $initial }}" placeholder="Ketik SKU / Nama...">
                    </div>

                    <div class="col-6 col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>

                    <div class="col-6 col-md-1">
                        <a href="{{ url('/cashier/products/search') }}" class="btn btn-light w-100">Reset</a>
                    </div>
                </form>

                <div class="mt-3 text-muted" id="product_page_hint">
                    @if (!$canSearchLocal)
                        Minimal 2 karakter untuk mencari.
                    @else
                        Hasil: {{ $rowsLocal->count() }} item
                    @endif
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-hover table-lg mb-0">
                        <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Dipakai</th>
                            <th>Tersedia</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                            @include('cashier.products.partials._rows', [
                                'rows' => $rowsLocal,
                                'canSearch' => $canSearchLocal,
                                'txId' => null,
                            ])
                        </tbody>
                    </table>
                </div>

                <div class="mt-2 text-muted" style="font-size: 12px;">
                    Untuk menambah ke transaksi, buka transaksi lalu gunakan pencarian di halaman transaksi.
                </div>
            </div>
        </div>
    </section>
@endsection