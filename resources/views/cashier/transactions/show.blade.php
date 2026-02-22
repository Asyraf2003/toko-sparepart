@extends('cashier.layouts.app')

@section('title', 'Kasir - Detail Nota')

@section('page_heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">Detail Nota</h3>
            <div class="text-muted">{{ $tx->transaction_number ?? '' }}</div>
        </div>

        <div>
            <a href="{{ url('/cashier/transactions/today') }}"
               class="btn btn-light d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>
@endsection

@section('content')
    <section class="section" id="tx_show_root" data-tx-id="{{ (int) $tx->id }}">
        <div class="row">

            <div class="col-12 col-lg-8 order-1 order-lg-1">
                {{-- search area tidak kita fragment-kan (biar JS search tetap stabil) --}}
                @include('cashier.transactions.partials._product_search')

                <div id="tx_alerts">
                    @include('cashier.transactions.partials._alerts')
                </div>

                <div id="tx_part_lines">
                    @include('cashier.transactions.partials._part_lines')
                </div>

                <div id="tx_service_lines">
                    @include('cashier.transactions.partials._service_lines')
                </div>
            </div>

            <div class="col-12 col-lg-4 order-2 order-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Informasi Nota</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <p><b>ID:</b> {{ $tx->id }}</p>
                            <p><b>No:</b> {{ $tx->transaction_number }}</p>
                            <p><b>Business Date:</b> {{ $tx->business_date }}</p>
                            <p><b>Status:</b> {{ $tx->status }}</p>
                            <p><b>Payment Status:</b> {{ $tx->payment_status }}</p>
                            <p><b>Payment Method:</b> {{ $tx->payment_method ?? '-' }}</p>

                            <p><b>Rounding Mode:</b> {{ $tx->rounding_mode ?? '-' }}</p>
                            <p><b>Rounding Amount:</b> {{ $tx->rounding_amount ?? 0 }}</p>

                            <p><b>Cash Received:</b> {{ $tx->cash_received ?? '-' }}</p>
                            <p><b>Cash Change:</b> {{ $tx->cash_change ?? '-' }}</p>
                            <p><b>Net Cash:</b>
                                {{ ($tx->cash_received !== null && $tx->cash_change !== null) ? ($tx->cash_received - $tx->cash_change) : '-' }}
                            </p>

                            <hr>

                            <p><b>Nama:</b> {{ $tx->customer_name ?? '-' }}</p>
                            <p><b>HP:</b> {{ $tx->customer_phone ?? '-' }}</p>
                            <p><b>Plat:</b> {{ $tx->vehicle_plate ?? '-' }}</p>
                            <p><b>Note:</b> {{ $tx->note ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div id="tx_customer_form">
                    @include('cashier.transactions.partials._customer_form')
                </div>

                <div id="tx_cash_calculator">
                    @include('cashier.transactions.partials._cash_calculator')
                </div>

                <div id="tx_summary_actions">
                    @include('cashier.transactions.partials._summary_actions')
                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
    @include('cashier.transactions.partials._show_scripts')
@endpush