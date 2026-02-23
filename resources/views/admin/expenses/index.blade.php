@extends('shared.layouts.app')

@section('title', 'Operasional')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Operasional</h3>
            <p class="text-muted mb-0">Catatan pengeluaran. Pencarian berdasarkan kategori.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ url('/admin/expenses/create') }}">Tambah Operasional</a>
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
            <form class="row g-2 align-items-end" method="get" action="{{ url('/admin/expenses') }}">
                <div class="col-12 col-md-6">
                    <label class="form-label">Cari kategori</label>
                    <input class="form-control" type="text" name="q" value="{{ $q }}">
                </div>

                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </div>

                <div class="col-auto">
                    <a class="btn btn-outline-secondary" href="{{ url('/admin/expenses') }}">Reset</a>
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
                        <th style="width: 140px;">Tanggal</th>
                        <th style="width: 220px;">Kategori</th>
                        <th class="text-end" style="width: 160px;">Jumlah</th>
                        <th>Catatan</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $r->expense_date }}</td>
                            <td class="fw-semibold">{{ $r->category }}</td>
                            <td class="text-end">{{ $fmt($r->amount) }}</td>
                            <td>{{ $r->note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted">Tidak ada data</td>
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