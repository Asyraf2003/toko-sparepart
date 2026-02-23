@extends('shared.layouts.app')

@section('title', 'Karyawan')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Karyawan</h3>
            <p class="text-muted mb-0">Kelola karyawan dan pinjaman.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ url('/admin/employees/create') }}">Tambah Karyawan</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = function (int $v): string {
            return number_format((float) $v, 0, ',', '.');
        };
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Nama</th>
                        <th style="width: 110px;">Aktif?</th>
                        <th class="text-end" style="width: 200px;">Sisa Pinjaman</th>
                        <th style="width: 140px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        @php
                            $outstanding = (int) ($outstandingByEmployeeId[$r->id] ?? 0);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $r->name }}</td>
                            <td>
                                @if ($r->is_active)
                                    <span class="badge bg-success">YA</span>
                                @else
                                    <span class="badge bg-secondary">TIDAK</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $fmt($outstanding) }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ url('/admin/employees/'.$r->id.'/loans/create') }}">
                                    Beri Pinjaman
                                </a>
                            </td>
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