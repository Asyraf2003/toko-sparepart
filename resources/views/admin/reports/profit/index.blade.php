@extends('shared.layouts.app')

@section('title', 'Profit Report')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Profit Report</h3>
            <p class="text-muted mb-0">Gunakan filter periode untuk menampilkan ringkasan & detail.</p>
        </div>
        <div class="d-flex gap-2">
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a class="btn btn-outline-primary"
                   target="_blank" rel="noopener"
                   href="{{ url('/admin/reports/profit/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
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
                                <tr><th style="width: 240px;">Revenue Part</th><td class="text-end">{{ $fmt($result->summary->revenuePart) }}</td></tr>
                                <tr><th>Revenue Service</th><td class="text-end">{{ $fmt($result->summary->revenueService) }}</td></tr>
                                <tr><th>Rounding</th><td class="text-end">{{ $fmt($result->summary->roundingAmount) }}</td></tr>
                                <tr><th class="fw-bold">Revenue Total</th><td class="text-end fw-bold">{{ $fmt($result->summary->revenueTotal) }}</td></tr>
                                <tr><th>COGS Total</th><td class="text-end">{{ $fmt($result->summary->cogsTotal) }}</td></tr>
                                <tr><th>Expenses Total</th><td class="text-end">{{ $fmt($result->summary->expensesTotal) }}</td></tr>
                                <tr><th>Payroll Gross</th><td class="text-end">{{ $fmt($result->summary->payrollGross) }}</td></tr>
                                <tr><th class="fw-bold">Net Profit</th><td class="text-end fw-bold">{{ $fmt($result->summary->netProfit) }}</td></tr>
                                <tr><th>Missing COGS Qty</th><td class="text-end">{{ $result->summary->missingCogsQty }}</td></tr>
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
                                    <th>Period</th>
                                    <th class="text-end">Revenue Part</th>
                                    <th class="text-end">Revenue Service</th>
                                    <th class="text-end">Rounding</th>
                                    <th class="text-end">Revenue Total</th>
                                    <th class="text-end">COGS</th>
                                    <th class="text-end">Expenses</th>
                                    <th class="text-end">Payroll Gross</th>
                                    <th class="text-end">Net Profit</th>
                                    <th class="text-end">Missing COGS Qty</th>
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
                                <label class="form-label">From</label>
                                <input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">To</label>
                                <input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Granularity</label>
                                <select class="form-select" name="granularity">
                                    <option value="weekly" @selected(($filters['granularity'] ?? 'weekly') === 'weekly')>weekly</option>
                                    <option value="monthly" @selected(($filters['granularity'] ?? 'weekly') === 'monthly')>monthly</option>
                                </select>
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Apply</button>
                                <a class="btn btn-outline-secondary" href="{{ url('/admin/reports/profit') }}">Reset</a>
                            </div>

                            @if (!empty($filters['from']) && !empty($filters['to']))
                                <div class="col-12">
                                    <a class="btn btn-outline-primary w-100"
                                       target="_blank" rel="noopener"
                                       href="{{ url('/admin/reports/profit/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
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