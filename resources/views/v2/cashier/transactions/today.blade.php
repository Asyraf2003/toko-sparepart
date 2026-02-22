@extends('v2.cashier.layouts.app')

@section('title', 'Kasir - Transaksi Hari Ini')

@section('page_heading')
    <div class="page-heading">
        <h3>Transaksi Hari Ini ({{ $today }})</h3>
    </div>
@endsection

@section('content')
    <section class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h4>Filter</h4>
                </div>
                <div class="card-body">
                    <form method="get" action="{{ url('/cashier/transactions/today') }}" class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="" @if(($status ?? '') === '') selected @endif>ALL</option>
                                <option value="DRAFT" @if(($status ?? '') === 'DRAFT') selected @endif>DRAFT</option>
                                <option value="OPEN" @if(($status ?? '') === 'OPEN') selected @endif>OPEN</option>
                                <option value="COMPLETED" @if(($status ?? '') === 'COMPLETED') selected @endif>COMPLETED</option>
                                <option value="VOID" @if(($status ?? '') === 'VOID') selected @endif>VOID</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Cari No</label>
                            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="INV-..." class="form-control">
                        </div>

                        <div class="col-12 col-md-auto">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </form>

                    <div class="mt-3">
                        <form method="post" action="{{ url('/cashier/transactions') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                + Buat Nota Baru (DRAFT)
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Daftar Transaksi</h4>
                </div>
                <div class="card-body">
                    @if($rows->count() === 0)
                        <p class="mb-0">Belum ada transaksi hari ini.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-lg">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>No</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Method</th>
                                    <th>Rounding</th>
                                    @if($hasCustomerName)
                                        <th>Customer</th>
                                    @endif
                                    @if($hasVehiclePlate)
                                        <th>Plat</th>
                                    @endif
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $r)
                                    <tr>
                                        <td>{{ $r->id }}</td>
                                        <td>{{ $r->transaction_number }}</td>
                                        <td>{{ $r->status }}</td>
                                        <td>{{ $r->payment_status }}</td>
                                        <td>{{ $r->payment_method ?? '-' }}</td>
                                        <td>{{ $r->rounding_amount ?? 0 }}</td>
                                        @if($hasCustomerName)
                                            <td>{{ $r->customer_name ?? '-' }}</td>
                                        @endif
                                        @if($hasVehiclePlate)
                                            <td>{{ $r->vehicle_plate ?? '-' }}</td>
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

                    <div class="mt-3">
                        <form method="post" action="{{ url('/logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-light">Logout</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection