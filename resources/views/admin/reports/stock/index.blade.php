@extends('shared.layouts.app')

@section('title', 'Stock Report')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Stock Report</h3>
            <p class="text-muted mb-0">Ringkasan stok dan low-stock.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary"
               target="_blank" rel="noopener"
               href="{{ url('/admin/reports/stock/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                Export PDF
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        {{-- LIST (kiri 2/3) --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Ringkasan</div>
                    <ul class="mb-0">
                        <li>Count: {{ $result->summary->count }}</li>
                        <li>Low Stock Count (available &lt;= threshold): {{ $result->summary->lowStockCount }}</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">Detail</div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="width: 120px;">SKU</th>
                                <th>Nama</th>
                                <th style="width: 90px;">Active</th>
                                <th class="text-end" style="width: 120px;">Threshold</th>
                                <th class="text-end" style="width: 120px;">On Hand</th>
                                <th class="text-end" style="width: 120px;">Reserved</th>
                                <th class="text-end" style="width: 120px;">Available</th>
                                <th style="width: 110px;">Low Stock</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($result->rows as $r)
                                <tr>
                                    <td class="fw-semibold">{{ $r->sku }}</td>
                                    <td>{{ $r->name }}</td>
                                    <td>
                                        @if ($r->isActive)
                                            <span class="badge bg-success">YES</span>
                                        @else
                                            <span class="badge bg-secondary">NO</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $r->minStockThreshold }}</td>
                                    <td class="text-end">{{ $r->onHandQty }}</td>
                                    <td class="text-end">{{ $r->reservedQty }}</td>
                                    <td class="text-end fw-semibold">{{ $r->availableQty }}</td>
                                    <td>
                                        @if ($r->isLowStock)
                                            <span class="badge bg-danger">YES</span>
                                        @else
                                            <span class="badge bg-success">NO</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (is_object($result->rows) && method_exists($result->rows, 'links'))
                        <div class="mt-3">
                            {{ $result->rows->links('vendor.pagination.mazer') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- FILTER (kanan 1/3) --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Filter</div>

                    <form method="get" action="{{ url('/admin/reports/stock') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Search (sku/nama)</label>
                                <input class="form-control" type="text" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="190">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Only Active</label>
                                <select class="form-select" name="only_active">
                                    <option value="1" @selected(($filters['only_active'] ?? '1') === '1')>Yes</option>
                                    <option value="0" @selected(($filters['only_active'] ?? '1') === '0')>No</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Limit</label>
                                <input class="form-control" type="number" name="limit" min="1" max="2000" value="{{ $filters['limit'] ?? 500 }}">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Apply</button>
                                <a class="btn btn-outline-secondary" href="{{ url('/admin/reports/stock') }}">Reset</a>
                            </div>

                            <div class="col-12">
                                <a class="btn btn-outline-primary w-100"
                                   target="_blank" rel="noopener"
                                   href="{{ url('/admin/reports/stock/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                                    Export PDF
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection