@extends('shared.layouts.app')

@section('title', 'Laporan Laba')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Laporan Laba</h3>
            <p class="text-muted mb-0">Gunakan filter periode untuk menampilkan ringkasan & detail.</p>
        </div>
        <div class="d-flex gap-2">
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a class="btn btn-outline-primary"
                   target="_blank" rel="noopener"
                   href="{{ url('/admin/reports/profit/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                    Ekspor PDF
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
                        <p class="mb-0">Isi periode (dari/sampai) untuk menampilkan data.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Ringkasan</div>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <tbody>
                                <tr><th style="width: 240px;">Pendapatan Part</th><td class="text-end">{{ $fmt($result->summary->revenuePart) }}</td></tr>
                                <tr><th>Pendapatan Jasa</th><td class="text-end">{{ $fmt($result->summary->revenueService) }}</td></tr>
                                <tr><th>Pembulatan</th><td class="text-end">{{ $fmt($result->summary->roundingAmount) }}</td></tr>
                                <tr><th class="fw-bold">Total Pendapatan</th><td class="text-end fw-bold">{{ $fmt($result->summary->revenueTotal) }}</td></tr>
                                <tr><th>Total HPP</th><td class="text-end">{{ $fmt($result->summary->cogsTotal) }}</td></tr>
                                <tr><th>Total Operasional</th><td class="text-end">{{ $fmt($result->summary->expensesTotal) }}</td></tr>
                                <tr><th>Total Payroll (Kotor)</th><td class="text-end">{{ $fmt($result->summary->payrollGross) }}</td></tr>
                                <tr><th class="fw-bold">Laba Bersih</th><td class="text-end fw-bold">{{ $fmt($result->summary->netProfit) }}</td></tr>
                                <tr><th>Qty HPP Hilang</th><td class="text-end">{{ $result->summary->missingCogsQty }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Detail ({{ $result->granularity }})</div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th class="text-end">Pendapatan Part</th>
                                    <th class="text-end">Pendapatan Jasa</th>
                                    <th class="text-end">Pembulatan</th>
                                    <th class="text-end">Total Pendapatan</th>
                                    <th class="text-end">HPP</th>
                                    <th class="text-end">Operasional</th>
                                    <th class="text-end">Payroll (Kotor)</th>
                                    <th class="text-end">Laba Bersih</th>
                                    <th class="text-end">Qty HPP Hilang</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($result->rows as $r)
                                    <tr>
                                        <td class="fw-semibold">{{ $r->periodLabel }}</td>
                                        <td class="text-end">{{ $fmt($r->revenuePart) }}</td>
                                        <td class="text-end">{{ $fmt($r->revenueService) }}</td>
                                        <td class="text-end">{{ $fmt($r->roundingAmount) }}</td>
                                        <td class="text-end fw-semibold">{{ $fmt($r->revenueTotal) }}</td>
                                        <td class="text-end">{{ $fmt($r->cogsTotal) }}</td>
                                        <td class="text-end">{{ $fmt($r->expensesTotal) }}</td>
                                        <td class="text-end">{{ $fmt($r->payrollGross) }}</td>
                                        <td class="text-end fw-semibold">{{ $fmt($r->netProfit) }}</td>
                                        <td class="text-end">{{ $r->missingCogsQty }}</td>
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

                    <form method="get" action="{{ url('/admin/reports/profit') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Dari</label>
                                <input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Sampai</label>
                                <input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Granularitas</label>
                                <select class="form-select" name="granularity">
                                    <option value="weekly" @selected(($filters['granularity'] ?? 'weekly') === 'weekly')>mingguan</option>
                                    <option value="monthly" @selected(($filters['granularity'] ?? 'weekly') === 'monthly')>bulanan</option>
                                </select>
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Terapkan</button>
                                <a class="btn btn-outline-secondary" href="{{ url('/admin/reports/profit') }}">Reset</a>
                            </div>

                            @if (!empty($filters['from']) && !empty($filters['to']))
                                <div class="col-12">
                                    <a class="btn btn-outline-primary w-100"
                                       target="_blank" rel="noopener"
                                       href="{{ url('/admin/reports/profit/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                                        Ekspor PDF
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