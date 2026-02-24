@extends('shared.layouts.app')

@section('title', 'Pembelian (Supplier)')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Pembelian (Supplier)</h3>
            <p class="text-muted mb-0">Daftar invoice pembelian dari supplier (status bayar + jatuh tempo).</p>
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

        $status = $filters['status'] ?? 'all';
        $bucket = $filters['bucket'] ?? 'all';
        $limit = $filters['limit'] ?? 200;
    @endphp

    <div class="card">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="{{ url('/admin/purchases') }}">
                <div class="col-12 col-md-4">
                    <label class="form-label">Cari (No Faktur / Supplier)</label>
                    <input class="form-control" type="text" name="q" value="{{ $q }}">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" @selected($status === 'all')>Semua</option>
                        <option value="unpaid" @selected($status === 'unpaid')>UNPAID</option>
                        <option value="paid" @selected($status === 'paid')>PAID</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Bucket</label>
                    <select class="form-select" name="bucket">
                        <option value="all" @selected($bucket === 'all')>Semua</option>
                        <option value="due_h5" @selected($bucket === 'due_h5')>Jatuh Tempo H-5</option>
                        <option value="overdue" @selected($bucket === 'overdue')>Overdue</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Limit</label>
                    <input class="form-control" type="number" name="limit" min="1" max="2000" value="{{ $limit }}">
                </div>

                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Terapkan</button>
                </div>

                <div class="col-auto">
                    <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">Reset</a>
                </div>

                <div class="col-12">
                    <div class="text-muted mt-2">Total (halaman ini): {{ $total ?? '-' }}</div>
                    <div class="text-muted small">Hari ini: {{ $today }} · Target H-5: {{ $targetDue }}</div>
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
                        <th style="width: 110px;">Tgl Kirim</th>
                        <th style="width: 110px;">Due</th>
                        <th style="width: 120px;">Status</th>
                        <th>No Faktur</th>
                        <th>Supplier</th>
                        <th class="text-end">Total Akhir</th>
                        <th style="width: 110px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        @php
                            $ps = $r->payment_status ?? 'UNPAID';
                            $isPaid = strtoupper((string) $ps) === 'PAID';
                            $due = $r->due_date;
                            $badge = null;

                            if (!$isPaid && $due !== null && (string)$due !== '') {
                                if ((string)$due < (string)$today) {
                                    $badge = ['bg' => 'bg-danger', 'text' => 'OVERDUE'];
                                } elseif ((string)$due === (string)$targetDue) {
                                    $badge = ['bg' => 'bg-warning text-dark', 'text' => 'H-5'];
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $r->tgl_kirim }}</td>
                            <td>
                                {{ $due ?? '-' }}
                                @if ($badge !== null)
                                    <span class="badge {{ $badge['bg'] }} ms-1">{{ $badge['text'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($isPaid)
                                    <span class="badge bg-success">PAID</span>
                                    <div class="text-muted small">{{ $r->paid_at ?? '' }}</div>
                                @else
                                    <span class="badge bg-danger">UNPAID</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('/admin/purchases/'.$r->id) }}">{{ $r->no_faktur }}</a>
                            </td>
                            <td>{{ $r->supplier_name }}</td>
                            <td class="text-end fw-semibold">{{ $fmt($r->grand_total) }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ url('/admin/purchases/'.$r->id) }}">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">Tidak ada data</td>
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