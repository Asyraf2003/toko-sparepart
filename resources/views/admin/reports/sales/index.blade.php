@extends('shared.layouts.app')

@section('title', 'Sales Report')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Sales Report</h3>
            <p class="text-muted mb-0">Filter transaksi untuk ringkasan dan detail.</p>
        </div>
        <div class="d-flex gap-2">
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a class="btn btn-outline-primary"
                   target="_blank" rel="noopener"
                   href="{{ url('/admin/reports/sales/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
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

        $fmtN = function (?int $v) use ($fmt): string {
            return $v === null ? '-' : $fmt($v);
        };

        $fmtNet = function (?int $received, ?int $change) use ($fmt): string {
            if ($received === null || $change === null) {
                return '-';
            }
            return $fmt($received - $change);
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
                                <tr><th style="width: 240px;">Count</th><td class="text-end">{{ $result->summary->count }}</td></tr>
                                <tr><th>Revenue Part</th><td class="text-end">{{ $fmt($result->summary->partSubtotal) }}</td></tr>
                                <tr><th>Revenue Service</th><td class="text-end">{{ $fmt($result->summary->serviceSubtotal) }}</td></tr>
                                <tr><th>Rounding</th><td class="text-end">{{ $fmt($result->summary->roundingAmount) }}</td></tr>
                                <tr><th class="fw-bold">Grand Total</th><td class="text-end fw-bold">{{ $fmt($result->summary->grandTotal) }}</td></tr>
                                <tr><th class="fw-bold">Cash Received Total</th><td class="text-end fw-bold">{{ $fmt($result->summary->cashReceivedTotal) }}</td></tr>
                                <tr><th class="fw-bold">Cash Change Total</th><td class="text-end fw-bold">{{ $fmt($result->summary->cashChangeTotal) }}</td></tr>
                                <tr><th class="fw-bold">Cash Net Total</th><td class="text-end fw-bold">{{ $fmt($result->summary->cashNetTotal) }}</td></tr>
                                <tr><th>COGS Total</th><td class="text-end">{{ $fmt($result->summary->cogsTotal) }}</td></tr>
                                <tr><th>Missing COGS Qty</th><td class="text-end">{{ $result->summary->missingCogsQty }}</td></tr>
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
                                    <th style="width: 120px;">Date</th>
                                    <th style="width: 150px;">No</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 120px;">Pay Status</th>
                                    <th style="width: 130px;">Pay Method</th>
                                    <th style="width: 110px;">Cashier</th>
                                    <th class="text-end">Part</th>
                                    <th class="text-end">Service</th>
                                    <th class="text-end">Rounding</th>
                                    <th class="text-end">Grand</th>
                                    <th class="text-end">Cash Received</th>
                                    <th class="text-end">Cash Change</th>
                                    <th class="text-end">Cash Net</th>
                                    <th class="text-end">COGS</th>
                                    <th class="text-end">Missing COGS Qty</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($result->rows as $r)
                                    <tr>
                                        <td>{{ $r->businessDate }}</td>
                                        <td class="fw-semibold">{{ $r->transactionNumber }}</td>
                                        <td><span class="badge bg-secondary">{{ $r->status }}</span></td>
                                        <td>{{ $r->paymentStatus }}</td>
                                        <td>{{ $r->paymentMethod ?? '-' }}</td>
                                        <td>{{ $r->cashierUserId }}</td>
                                        <td class="text-end">{{ $fmt($r->partSubtotal) }}</td>
                                        <td class="text-end">{{ $fmt($r->serviceSubtotal) }}</td>
                                        <td class="text-end">{{ $fmt($r->roundingAmount) }}</td>
                                        <td class="text-end fw-semibold">{{ $fmt($r->grandTotal) }}</td>
                                        <td class="text-end">{{ $fmtN($r->cashReceived) }}</td>
                                        <td class="text-end">{{ $fmtN($r->cashChange) }}</td>
                                        <td class="text-end">{{ $fmtNet($r->cashReceived, $r->cashChange) }}</td>
                                        <td class="text-end">{{ $fmt($r->cogsTotal) }}</td>
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

                    <form method="get" action="{{ url('/admin/reports/sales') }}">
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
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">(all)</option>
                                    @foreach (['DRAFT','OPEN','COMPLETED','VOID'] as $opt)
                                        <option value="{{ $opt }}" @selected(($filters['status'] ?? '') === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Payment Status</label>
                                <select class="form-select" name="payment_status">
                                    <option value="">(all)</option>
                                    @foreach (['UNPAID','PAID'] as $opt)
                                        <option value="{{ $opt }}" @selected(($filters['payment_status'] ?? '') === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method">
                                    <option value="">(all)</option>
                                    @foreach (['CASH','TRANSFER'] as $opt)
                                        <option value="{{ $opt }}" @selected(($filters['payment_method'] ?? '') === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cashier User ID</label>
                                <input class="form-control" type="number" name="cashier_user_id" min="1" value="{{ $filters['cashier_user_id'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Limit</label>
                                <input class="form-control" type="number" name="limit" min="1" max="1000" value="{{ $filters['limit'] ?? 200 }}">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Apply</button>
                                <a class="btn btn-outline-secondary" href="{{ url('/admin/reports/sales') }}">Reset</a>
                            </div>

                            @if (!empty($filters['from']) && !empty($filters['to']))
                                <div class="col-12">
                                    <a class="btn btn-outline-primary w-100"
                                       target="_blank" rel="noopener"
                                       href="{{ url('/admin/reports/sales/pdf') }}?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
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