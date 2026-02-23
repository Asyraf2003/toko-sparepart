@extends('shared.layouts.app')

@section('title', 'Purchasing Report')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Purchasing Report</h3>
            <p class="text-muted mb-0">Gunakan periode untuk menampilkan ringkasan dan detail.</p>
        </div>
        <div class="d-flex gap-2">
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a class="btn btn-outline-primary"
                   target="_blank" rel="noopener"
                   href="{{ url('/admin/reports/purchasing/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                    Export PDF
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = function (int $v): string {
            return number_format($v, 0, ',', '.');
        };
    @endphp

    <div class="row g-3">
        {{-- LIST (kiri 2/3) --}}
        <div class="col-12 col-lg-8">
            @if ($result === null)
                <div class="card">
                    <div class="card-body">
                        <p class="mb-0">Isi periode (from/to) untuk menampilkan data.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Ringkasan</div>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <tbody>
                                <tr><th style="width: 220px;">Count</th><td class="text-end">{{ $result->summary->count }}</td></tr>
                                <tr><th>Total Bruto</th><td class="text-end">{{ $fmt($result->summary->totalBruto) }}</td></tr>
                                <tr><th>Total Diskon</th><td class="text-end">{{ $fmt($result->summary->totalDiskon) }}</td></tr>
                                <tr><th>Total Pajak</th><td class="text-end">{{ $fmt($result->summary->totalPajak) }}</td></tr>
                                <tr><th class="fw-bold">Grand Total</th><td class="text-end fw-bold">{{ $fmt($result->summary->grandTotal) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Detail</div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 130px;">Tgl Kirim</th>
                                    <th style="width: 170px;">No Faktur</th>
                                    <th>Supplier</th>
                                    <th class="text-end">Total Bruto</th>
                                    <th class="text-end">Total Diskon</th>
                                    <th class="text-end">Total Pajak</th>
                                    <th class="text-end">Grand Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($result->rows as $r)
                                    <tr>
                                        <td>{{ $r->tglKirim }}</td>
                                        <td class="fw-semibold">{{ $r->noFaktur }}</td>
                                        <td>{{ $r->supplierName }}</td>
                                        <td class="text-end">{{ $fmt($r->totalBruto) }}</td>
                                        <td class="text-end">{{ $fmt($r->totalDiskon) }}</td>
                                        <td class="text-end">{{ $fmt($r->totalPajak) }}</td>
                                        <td class="text-end fw-semibold">{{ $fmt($r->grandTotal) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- FILTER (kanan 1/3) --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Filter</div>

                    <form method="get" action="{{ url('/admin/reports/purchasing') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">From</label>
                                <input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">To</label>
                                <input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">No Faktur (contains)</label>
                                <input class="form-control" type="text" name="no_faktur" value="{{ $filters['no_faktur'] ?? '' }}" maxlength="64">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Limit</label>
                                <input class="form-control" type="number" name="limit" min="1" max="1000" value="{{ $filters['limit'] ?? 200 }}">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Apply</button>
                                <a class="btn btn-outline-secondary" href="{{ url('/admin/reports/purchasing') }}">Reset</a>
                            </div>

                            @if (!empty($filters['from']) && !empty($filters['to']))
                                <div class="col-12">
                                    <a class="btn btn-outline-primary w-100"
                                       target="_blank" rel="noopener"
                                       href="{{ url('/admin/reports/purchasing/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                                        Export PDF
                                    </a>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection