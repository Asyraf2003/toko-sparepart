@extends('v2.cashier.layouts.app')

@section('title', 'Kasir - Transaksi Hari Ini')

@section('page_heading')
    <div class="page-heading">
        <h3 class="mb-1">Transaksi Hari Ini</h3>
        <div class="text-muted">({{ $today }})</div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">

            {{-- KANAN: Aksi + Filter --}}
            <div class="col-12 col-lg-4 order-1 order-lg-2">

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Aksi Cepat</h4>
                    </div>
                    <div class="card-body d-flex gap-2 flex-wrap">
                        <form method="post" action="{{ url('/cashier/transactions') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                + Buat Nota Baru (DRAFT)
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Filter</h4>
                    </div>
                    <div class="card-body">
                        <form method="get" action="{{ url('/cashier/transactions/today') }}" class="row g-2 align-items-end">
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="" @if(($status ?? '') === '') selected @endif>ALL</option>
                                    <option value="DRAFT" @if(($status ?? '') === 'DRAFT') selected @endif>DRAFT</option>
                                    <option value="OPEN" @if(($status ?? '') === 'OPEN') selected @endif>OPEN</option>
                                    <option value="COMPLETED" @if(($status ?? '') === 'COMPLETED') selected @endif>COMPLETED</option>
                                    <option value="VOID" @if(($status ?? '') === 'VOID') selected @endif>VOID</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cari No</label>
                                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="INV-..." class="form-control">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <a class="btn btn-light" href="{{ url('/cashier/transactions/today') }}">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            {{-- KIRI: Daftar transaksi (utama) --}}
            <div class="col-12 col-lg-8 order-2 order-lg-1">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar Transaksi</h4>
                        <div class="text-muted">{{ $rows->count() }} data</div>
                    </div>

                    <div class="card-body">
                        @if($rows->count() === 0)
                            <p class="mb-0">Belum ada transaksi hari ini.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-lg mb-0">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>No</th>
                                        @if($hasCustomerName)
                                            <th>Customer</th>
                                        @endif
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Method</th>
                                        <th class="d-none">Rounding</th>
                                        @if($hasVehiclePlate)
                                            <th class="d-none">Plat</th>
                                        @endif
                                        <th>Aksi</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($rows as $r)
                                        <tr>
                                            <td>{{ $r->id }}</td>
                                            <td>{{ $r->transaction_number }}</td>
                                            @if($hasCustomerName)
                                                <td>{{ $r->customer_name ?? '-' }}</td>
                                            @endif
                                            <td>{{ $r->status }}</td>
                                            <td>{{ $r->payment_status }}</td>
                                            <td>{{ $r->payment_method ?? '-' }}</td>
                                            <td class="d-none">{{ $r->rounding_amount ?? 0 }}</td>
                                            @if($hasVehiclePlate)
                                                <td class="d-none">{{ $r->vehicle_plate ?? '-' }}</td>
                                            @endif
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="{{ url('/cashier/transactions/'.$r->id) }}">
                                                    Buka
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection