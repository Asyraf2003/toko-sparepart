@extends('cashier.layouts.app')

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
                        <form method="get"
                              action="{{ url('/cashier/transactions/today') }}"
                              class="row g-2 align-items-end"
                              id="today_filter_form">
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="today_status">
                                    <option value="" @if(($status ?? '') === '') selected @endif>ALL</option>
                                    <option value="DRAFT" @if(($status ?? '') === 'DRAFT') selected @endif>DRAFT</option>
                                    <option value="OPEN" @if(($status ?? '') === 'OPEN') selected @endif>OPEN</option>
                                    <option value="COMPLETED" @if(($status ?? '') === 'COMPLETED') selected @endif>COMPLETED</option>
                                    <option value="VOID" @if(($status ?? '') === 'VOID') selected @endif>VOID</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cari No</label>
                                <input type="text"
                                       name="q"
                                       value="{{ $q ?? '' }}"
                                       placeholder="INV-..."
                                       class="form-control"
                                       id="today_q">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="today_apply_btn">Apply</button>
                                <a class="btn btn-light" href="{{ url('/cashier/transactions/today') }}" id="today_reset_link">Reset</a>
                            </div>

                            <div class="col-12">
                                <div class="text-muted" style="font-size: 12px;" id="today_hint"></div>
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
                        <div class="text-muted">
                            Total: <span id="today_total">{{ $rows->total() }}</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="today_list">
                            @include('cashier.transactions.partials._today_list', [
                                'rows' => $rows,
                                'hasCustomerName' => $hasCustomerName,
                                'hasVehiclePlate' => $hasVehiclePlate,
                            ])
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
    @include('cashier.transactions.partials._today_scripts')
@endpush