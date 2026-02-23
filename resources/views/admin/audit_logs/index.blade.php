@extends('shared.layouts.app')

@section('title', 'Catatan Audit')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Catatan Audit</h3>
            <p class="text-muted mb-0">Filter: pelaku/entitas/aksi/tanggal. Per halaman 10 baris.</p>
        </div>
    </div>
@endsection

@section('content')
    @php
        $perPage = 10;
        $page = (int) request()->query('page', 1);
        if ($page < 1) { $page = 1; }

        // $rows di sistem kamu adalah list<array{...}> (SearchAuditLogsResult).
        // Kita paginasi manual supaya tetap bisa pakai vendor.pagination.mazer.
        $items = $rows;

        if ($items instanceof \Illuminate\Contracts\Pagination\Paginator || $items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $paginator = $items;
        } else {
            if ($items instanceof \Illuminate\Support\Collection) {
                $items = $items->all();
            }
            if (!is_array($items)) {
                $items = is_iterable($items) ? iterator_to_array($items) : [];
            }

            $totalItems = count($items);
            $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $slice,
                $totalItems,
                $perPage,
                $page,
                [
                    'path' => url('/admin/audit-logs'),
                    'query' => request()->query(),
                ]
            );
        }

        $total = method_exists($paginator, 'total') ? (int) $paginator->total() : (int) $paginator->count();
        $first = method_exists($paginator, 'firstItem') ? $paginator->firstItem() : null;
        $last = method_exists($paginator, 'lastItem') ? $paginator->lastItem() : null;
    @endphp

    <div class="row g-3">
        {{-- LIST (kiri 2/3) --}}
        <div class="col-12 col-lg-8">
            <div id="audit_logs_fragment_root"
                 data-total="{{ $total }}"
                 data-first="{{ $first ?? '' }}"
                 data-last="{{ $last ?? '' }}">
                <div class="card">
                    <div class="card-body">
                        @if ($paginator->count() === 0)
                            <p class="mb-0">Tidak ada data.</p>
                        @else
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="text-muted" style="font-size: 12px;">
                                    @if ($first !== null && $last !== null)
                                        Menampilkan {{ $first }}–{{ $last }} dari {{ $total }}
                                    @endif
                                </div>
                                <div>
                                    {{ $paginator->appends(request()->query())->links('vendor.pagination.mazer') }}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width: 80px;">ID</th>
                                        <th style="width: 180px;">Waktu</th>
                                        <th>Pelaku</th>
                                        <th style="width: 200px;">Aksi</th>
                                        <th style="width: 220px;">Entitas</th>
                                        <th>Alasan</th>
                                        <th style="width: 90px;">Detail</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($paginator as $r)
                                        <tr>
                                            <td>{{ $r['id'] }}</td>
                                            <td>{{ $r['created_at'] }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $r['actor_name'] ?? '-' }}</div>
                                                <div class="text-muted small">{{ $r['actor_email'] ?? '' }}</div>
                                                <div class="text-muted small">ID: {{ $r['actor_id'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $r['action'] }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $r['entity_type'] }}</div>
                                                <div class="text-muted small">ID: {{ $r['entity_id'] ?? '-' }}</div>
                                            </td>
                                            <td>{{ $r['reason'] }}</td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="{{ url('/admin/audit-logs/'.$r['id']) }}">Buka</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER (kanan 1/3) --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Filter</div>

                    <form method="GET" action="{{ url('/admin/audit-logs') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Pelaku (nama/email mengandung)</label>
                                <input class="form-control" type="text" name="actor" value="{{ $filters['actor'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">ID Pelaku</label>
                                <input class="form-control" type="text" name="actor_id" value="{{ $filters['actor_id'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tipe Entitas</label>
                                <input class="form-control" type="text" name="entity_type" value="{{ $filters['entity_type'] ?? '' }}" placeholder="Transaksi/Produk/...">
                            </div>

                            <div class="col-12">
                                <label class="form-label">ID Entitas</label>
                                <input class="form-control" type="text" name="entity_id" value="{{ $filters['entity_id'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Aksi</label>
                                <input class="form-control" type="text" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="TRANSAKSI_BATAL/UBAH_HARGA/...">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tanggal Mulai</label>
                                <input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tanggal Sampai</label>
                                <input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Terapkan</button>
                                <a class="btn btn-outline-secondary" href="{{ url('/admin/audit-logs') }}">Reset</a>
                            </div>

                            <div class="col-12 text-muted small">
                                Per halaman: {{ $perPage }} baris.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection