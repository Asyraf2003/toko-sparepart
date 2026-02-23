@extends('shared.layouts.app')

@section('title', 'Edit Produk')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Edit Produk</h3>
            <p class="text-muted mb-0">Kelola info, harga, threshold, dan stok.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">← Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Validasi error</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $price = $row->sellPriceCurrent ?? null;
        $priceText = is_numeric($price) ? number_format((float) $price, 0, ',', '.') : (string) $price;
    @endphp

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Info</div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <tbody>
                            <tr><th style="width:220px;">SKU</th><td>{{ $row->sku }}</td></tr>
                            <tr><th>Nama</th><td>{{ $row->name }}</td></tr>
                            <tr><th>Harga</th><td>{{ $priceText }}</td></tr>
                            <tr><th>Min</th><td>{{ $row->minStockThreshold }}</td></tr>
                            <tr><th>On Hand</th><td>{{ $row->onHandQty }}</td></tr>
                            <tr><th>Reserved</th><td>{{ $row->reservedQty }}</td></tr>
                            <tr><th>Available</th><td>{{ $row->availableQty() }}</td></tr>
                            <tr>
                                <th>Low?</th>
                                <td>
                                    @if ($row->isLowStock())
                                        <span class="badge bg-danger">YES</span>
                                    @else
                                        <span class="badge bg-success">NO</span>
                                    @endif
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        {{-- Update Info Produk --}}
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Update Info Produk</div>

                    <form method="post" action="{{ url('/admin/products/'.$row->productId) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input class="form-control" type="text" name="sku" value="{{ old('sku', $row->sku) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input class="form-control" type="text" name="name" value="{{ old('name', $row->name) }}">
                        </div>

                        <div class="form-check mb-3">
                            @php
                                $activeChecked = old('is_active', $row->isActive ? '1' : '') ? true : false;
                            @endphp
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ $activeChecked ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <button class="btn btn-primary" type="submit">Update Info</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Set Harga Jual --}}
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Set Harga Jual</div>

                    <form method="post" action="{{ url('/admin/products/'.$row->productId.'/selling-price') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Harga Baru</label>
                            <input class="form-control" type="number" name="sell_price_current"
                                   value="{{ old('sell_price_current', (string) $row->sellPriceCurrent) }}"
                                   min="0" step="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note/Alasan (wajib)</label>
                            <input class="form-control" type="text" name="note" value="{{ old('note', '') }}" required>
                            <div class="form-text">Dicatat ke audit trail.</div>
                        </div>

                        <button class="btn btn-primary" type="submit">Update Harga</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Set Min Stock Threshold --}}
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Set Min Stock Threshold</div>

                    <form method="post" action="{{ url('/admin/products/'.$row->productId.'/min-threshold') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Threshold Baru</label>
                            <input class="form-control" type="number" name="min_stock_threshold"
                                   value="{{ old('min_stock_threshold', (string) $row->minStockThreshold) }}"
                                   min="0" step="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note/Alasan (wajib)</label>
                            <input class="form-control" type="text" name="note" value="{{ old('note', '') }}" required>
                            <div class="form-text">Dicatat ke audit trail.</div>
                        </div>

                        <button class="btn btn-primary" type="submit">Update Threshold</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Adjust Stock (On Hand) --}}
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Adjust Stock (On Hand)</div>

                    <form method="post" action="{{ url('/admin/products/'.$row->productId.'/adjust-stock') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Qty Delta (+ tambah / - kurang)</label>
                            <input class="form-control" type="number" name="qty_delta" value="{{ old('qty_delta', '0') }}" step="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note/Alasan (wajib)</label>
                            <input class="form-control" type="text" name="note" value="{{ old('note', '') }}" required>
                            <div class="form-text">Gunakan alasan yang jelas (stok opname, rusak, dll).</div>
                        </div>

                        <button class="btn btn-danger" type="submit">Adjust</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection