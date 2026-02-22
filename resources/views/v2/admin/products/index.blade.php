@extends('v2.admin.layouts.app')

@section('title', 'Produk & Stok')

@section('page_heading')
    <div class="page-heading">
        <h3>Produk & Stok</h3>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Produk dan Stok</h4>
            <x-v2.button href="{{ url('/admin/products/create') }}">Tambah Produk</x-v2.button>
        </div>

        <div class="card-body">
            <form method="get" action="{{ url('/admin/products') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <x-v2.input name="q" label="Cari (SKU/Nama)" :value="$q" />
                </div>

                <div class="col-12 col-md-auto">
                    <x-v2.button type="submit">Cari</x-v2.button>
                </div>

                <div class="col-12 col-md-auto">
                    <x-v2.button variant="light" href="{{ url('/admin/products') }}">Reset</x-v2.button>
                </div>
            </form>

            <div class="mt-3 text-muted">
                Total: {{ count($rows) }}
            </div>

            <div class="table-responsive mt-2">
                <table class="table table-hover table-lg">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>On Hand</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th>Min</th>
                        <th>Low?</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $r->sku }}</td>
                            <td>{{ $r->name }}</td>
                            <td>{{ $r->sellPriceCurrent }}</td>
                            <td>{{ $r->onHandQty }}</td>
                            <td>{{ $r->reservedQty }}</td>
                            <td>{{ $r->availableQty() }}</td>
                            <td>{{ $r->minStockThreshold }}</td>
                            <td>{{ $r->isLowStock() ? 'YES' : 'NO' }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ url('/admin/products/'.$r->productId.'/edit') }}">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">Tidak ada data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <a href="{{ url('/logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Logout
                </a>

                <form id="logout-form" method="post" action="{{ url('/logout') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
@endsection

@section('sidebar_menu')
    {{-- MENU ADMIN sengaja kosong: Anda akan isi berdasarkan keputusan Anda --}}
@endsection