@extends('cashier.layouts.app')

@section('title', 'Kasir - Detail Nota')

@section('page_heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        
        {{-- KIRI: Judul --}}
        <div>
            <h3 class="mb-0">Detail Nota</h3>
            <div class="text-muted">{{ $tx->transaction_number ?? '' }}</div>
        </div>

        {{-- KANAN: Tombol --}}
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
    <section class="section">
        <div class="row">

            {{-- KIRI: DETAIL + INPUT BARANG/JASA --}}
            <div class="col-12 col-lg-8 order-1 order-lg-1">
                
                @include('cashier.transactions.partials._product_search')
                @include('cashier.transactions.partials._part_lines')
                @include('cashier.transactions.partials._service_lines')
            </div>
            
            {{-- KANAN: PEMBAYARAN (ringkasan + cash calculator) --}}
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
                            <p><b>Rounding:</b> {{ $tx->rounding_amount ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                @include('cashier.transactions.partials._cash_calculator')
                @include('cashier.transactions.partials._summary_actions')
            </div>

        </div>
    </section>
@endsection