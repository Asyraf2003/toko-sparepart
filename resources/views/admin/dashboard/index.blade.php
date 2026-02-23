@extends('shared.layouts.app')

@section('title', 'Admin Dashboard')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Dashboard Admin</h3>
            <p class="text-muted mb-0">Ringkas: KPI, grafik, dan shortcut.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin?days=14') }}">14 hari</a>
            <a class="btn btn-outline-secondary" href="{{ url('/admin?days=30') }}">30 hari</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = fn(int $v) => number_format((float) $v, 0, ',', '.');

        $kpi = $dashboard['kpi'];
        $charts = $dashboard['charts'];
        $tables = $dashboard['tables'];
    @endphp

    {{-- KPI --}}
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Revenue Today ({{ $kpi['today']['business_date'] }})</div>
                    <div class="fs-4 fw-bold mt-1">Rp {{ $fmt($kpi['today']['revenue']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Transaksi Today</div>
                    <div class="fs-4 fw-bold mt-1">{{ $kpi['today']['tx_count'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Cash Net Today</div>
                    <div class="fs-4 fw-bold mt-1">Rp {{ $fmt($kpi['today']['cash_net']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Purchases MTD ({{ $kpi['mtd']['month_start'] }} → {{ $kpi['today']['business_date'] }})</div>
                    <div class="fs-4 fw-bold mt-1">Rp {{ $fmt($kpi['mtd']['purchases_total']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Expenses MTD</div>
                    <div class="fs-4 fw-bold mt-1">Rp {{ $fmt($kpi['mtd']['expenses_total']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Low Stock Count</div>
                    <div class="fs-4 fw-bold mt-1">{{ $kpi['low_stock_count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Revenue per Day</div>
                    <div id="chart_revenue_daily" style="min-height: 280px;"></div>
                    <div class="text-muted small mt-2">Sumber: transaksi COMPLETED+PAID (part+service+rounding).</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Payment Method Split</div>
                    <div id="chart_payment_split" style="min-height: 280px;"></div>
                    <div class="text-muted small mt-2">Nilai = revenue total per metode.</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Cash Net per Day</div>
                    <div id="chart_cash_net_daily" style="min-height: 260px;"></div>
                    <div class="text-muted small mt-2">Hanya transaksi CASH, net = received - change.</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">OHLC Revenue (Candlestick)</div>
                    <div id="chart_ohlc_daily" style="min-height: 260px;"></div>
                    <div class="text-muted small mt-2">Open/Close = transaksi pertama/terakhir per hari (completed_at).</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tables --}}
    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Low Stock Items (Top 10)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nama</th>
                                <th class="text-end">Avail</th>
                                <th class="text-end">Thr</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($tables['low_stock_items'] as $r)
                                <tr>
                                    <td class="fw-semibold">{{ $r['sku'] }}</td>
                                    <td>{{ $r['name'] }}</td>
                                    <td class="text-end">{{ $r['available'] }}</td>
                                    <td class="text-end">{{ $r['threshold'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Tidak ada data</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ url('/admin/reports/stock') }}">Buka Stock Report</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Recent Purchases (Top 10)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Tgl</th>
                                <th>No Faktur</th>
                                <th class="text-end">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($tables['recent_purchases'] as $r)
                                <tr>
                                    <td>{{ $r['tgl_kirim'] }}</td>
                                    <td class="fw-semibold">{{ $r['no_faktur'] }}</td>
                                    <td class="text-end">Rp {{ $fmt($r['grand_total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Tidak ada data</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ url('/admin/purchases') }}">Buka Purchases</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Recent Audit Logs (Top 10)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Entity</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($tables['recent_audits'] as $r)
                                <tr>
                                    <td class="text-muted small">{{ $r['created_at'] }}</td>
                                    <td class="fw-semibold">{{ $r['action'] }}</td>
                                    <td class="text-muted small">{{ $r['entity_type'] }}#{{ $r['entity_id'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Tidak ada data</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ url('/admin/audit-logs') }}">Buka Audit Logs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links (existing) --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Quick Links</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/products') }}">Produk</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/products/create') }}">Tambah Produk</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/purchases') }}">Invoice Pembelian</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/purchases/create') }}">Input Pembelian</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/employees') }}">Karyawan</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/expenses') }}">Pengeluaran</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/payroll') }}">Payroll</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/reports/sales') }}">Sales Report</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/reports/profit') }}">Profit Report</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/audit-logs') }}">Audit Logs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/extensions/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            if (typeof ApexCharts === 'undefined') return;

            const revenueDaily = @json($charts['revenue_daily']);
            const cashNetDaily = @json($charts['cash_net_daily']);
            const paymentSplit = @json($charts['payment_split']);
            const ohlcDaily = @json($charts['ohlc_daily']);

            const toSeries = (rows) => rows.map(r => ({ x: r.date, y: r.value }));

            // Bar: Revenue per day
            const elRev = document.querySelector('#chart_revenue_daily');
            if (elRev) {
                const options = {
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    series: [{ name: 'Revenue', data: toSeries(revenueDaily) }],
                    xaxis: { type: 'category' },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(elRev, options).render();
            }

            // Donut: Payment split
            const elPay = document.querySelector('#chart_payment_split');
            if (elPay) {
                const options = {
                    chart: { type: 'donut', height: 280 },
                    labels: paymentSplit.labels || [],
                    series: paymentSplit.series || [],
                    legend: { position: 'bottom' }
                };
                new ApexCharts(elPay, options).render();
            }

            // Line: Cash net daily
            const elCash = document.querySelector('#chart_cash_net_daily');
            if (elCash) {
                const options = {
                    chart: { type: 'line', height: 260, toolbar: { show: false } },
                    series: [{ name: 'Cash Net', data: toSeries(cashNetDaily) }],
                    stroke: { width: 2 },
                    dataLabels: { enabled: false },
                    xaxis: { type: 'category' }
                };
                new ApexCharts(elCash, options).render();
            }

            // Candlestick: OHLC
            const elOhlc = document.querySelector('#chart_ohlc_daily');
            if (elOhlc) {
                const options = {
                    chart: { type: 'candlestick', height: 260, toolbar: { show: false } },
                    series: [{ data: (ohlcDaily || []).map(r => ({ x: r.x, y: r.y })) }],
                    xaxis: { type: 'category' }
                };
                new ApexCharts(elOhlc, options).render();
            }
        })();
    </script>
@endpush