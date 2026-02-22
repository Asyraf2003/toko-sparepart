@extends('v2.cashier.layouts.app')

@section('title', 'Kasir - Detail Nota')

@section('page_heading')
    <div class="page-heading">
        <h3>Detail Nota</h3>
    </div>
@endsection

@section('content')
    <section class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-body">
                    <a href="{{ url('/cashier/transactions/today') }}">&larr; Kembali</a>

                    <div class="mt-3">
                        @include('v2.cashier.transactions.partials._alerts')
                    </div>

                    <div class="mt-3">
                        <p><b>ID:</b> {{ $tx->id }}</p>
                        <p><b>No:</b> {{ $tx->transaction_number }}</p>
                        <p><b>Business Date:</b> {{ $tx->business_date }}</p>
                        <p><b>Status:</b> {{ $tx->status }}</p>
                        <p><b>Payment Status:</b> {{ $tx->payment_status }}</p>
                        <p><b>Payment Method:</b> {{ $tx->payment_method ?? '-' }}</p>
                        <p><b>Rounding:</b> {{ $tx->rounding_amount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            @include('v2.cashier.transactions.partials._summary_actions')
            @include('v2.cashier.transactions.partials._cash_calculator')
            @include('v2.cashier.transactions.partials._product_search')
            @include('v2.cashier.transactions.partials._part_lines')
            @include('v2.cashier.transactions.partials._service_lines')

        </div>
    </section>
@endsection