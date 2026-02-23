@extends('shared.layouts.app')

@section('title', 'Pembelian (Supplier)')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Pembelian (Supplier)</h3>
            <p class="text-muted mb-0">Daftar invoice pembelian dari supplier.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ url('/admin/purchases/create') }}">Tambah Pembelian</a>
            <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Produk & Stok</a>
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

        $fmt = function ($v): string {
            if ($v === null) return '-';
            if (is_numeric($v)) return number_format((float) $v, 0, ',', '.');
            return (string) $v;
        };
    @endphp

    <div class="card">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="{{ url('/admin/purchases') }}">
                <div class="col-12 col-md-6">
                    <label class="form-label">Cari (No Faktur / Supplier)</label>
                    <input class="form-control" type="text" name="q" value="{{ $q }}">
                </div>

                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </div>

                <div class="col-auto">
                    <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">Reset</a>
                </div>

                <div class="col-12">
                    <div class="text-muted mt-2">Total: {{ $total ?? '-' }}</div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Tgl Kirim</th>
                        <th>No Faktur</th>
                        <th>Supplier</th>
                        <th class="text-end">Bruto</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Pajak</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-center">Aksi</th> </tr>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $r->tgl_kirim }}</td>
                            <td>
                                <a href="{{ url('/admin/purchases/'.$r->id) }}" class="fw-bold text-primary text-decoration-none">
                                    {{ $r->no_faktur }}
                                </a>
                            </td>
                            <td>{{ $r->supplier_name }}</td>
                            <td class="text-end">{{ $fmt($r->total_bruto) }}</td>
                            <td class="text-end">{{ $fmt($r->total_diskon) }}</td>
                            <td class="text-end">{{ $fmt($r->total_pajak) }}</td>
                            <td class="text-end fw-bold">{{ $fmt($r->grand_total) }}</td>
                            <td class="text-center">
                                <a href="{{ url('/admin/purchases/'.$r->id.'/edit') }}" 
                                class="btn btn-sm btn-outline-warning" 
                                title="Edit Data">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted text-center italic">Tidak ada data ditemukan</td>
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