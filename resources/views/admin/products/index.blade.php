@extends('shared.layouts.app')

@section('title', 'Produk dan Stok')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Produk dan Stok</h3>
            <p class="text-muted mb-0">Kelola daftar produk dan kontrol stok.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ url('/admin/products/create') }}">Tambah Produk</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $total = null;
        if (is_countable($rows)) {
            $total = count($rows);
        } elseif (is_object($rows) && method_exists($rows, 'count')) {
            $total = $rows->count();
        }
    @endphp

    <div class="card">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="{{ url('/admin/products') }}">
                <div class="col-12 col-md-6">
                    <label class="form-label">Cari (SKU/Nama)</label>
                    <input class="form-control" type="text" name="q" value="{{ $q }}">
                </div>

                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </div>

                <div class="col-auto">
                    <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Reset</a>
                </div>

                <div class="col-12">
                    <div class="text-muted mt-2">
                        Total: {{ $total ?? '-' }}
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">On Hand</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Min</th>
                        <th>Low?</th>
                        <th style="width: 110px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        @php
                            $price = $r->sellPriceCurrent;
                            $priceText = is_numeric($price) ? number_format((float) $price, 0, ',', '.') : (string) $price;
                        @endphp
                        <tr>
                            <td>{{ $r->sku }}</td>
                            <td>{{ $r->name }}</td>
                            <td class="text-end">{{ $priceText }}</td>
                            <td class="text-end">{{ $r->onHandQty }}</td>
                            <td class="text-end">{{ $r->reservedQty }}</td>
                            <td class="text-end">{{ $r->availableQty() }}</td>
                            <td class="text-end">{{ $r->minStockThreshold }}</td>
                            <td>
                                @if ($r->isLowStock())
                                    <span class="badge bg-danger">YES</span>
                                @else
                                    <span class="badge bg-success">NO</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ url('/admin/products/'.$r->productId.'/edit') }}">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted">Tidak ada data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if (is_object($rows) && method_exists($rows, 'links'))
                <div class="mt-3">
                    {{ $rows->links('vendor.pagination.mazer') }}
                </div>
            @endif
        </div>
    </div>
@endsection