@extends('shared.layouts.app')

@section('title', 'Admin Dashboard')

@section('page_heading')
    <div class="page-heading">
        <h3>Dashboard Admin</h3>
        <p class="text-muted mb-0">Ringkas: shortcut ke fitur utama.</p>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Master</div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/products') }}">Produk</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/products/create') }}">Tambah Produk</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Pembelian</div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/purchases') }}">Invoice Pembelian</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/purchases/create') }}">Input Pembelian</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">SDM</div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/employees') }}">Karyawan</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/employees/create') }}">Tambah Karyawan</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Pengeluaran & Payroll</div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/expenses') }}">Pengeluaran</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/payroll') }}">Payroll</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Laporan</div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/reports/sales') }}">Penjualan</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/reports/stock') }}">Stok</a>
                        <a class="btn btn-outline-primary" href="{{ url('/admin/reports/profit') }}">Profit</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Audit</div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="{{ url('/admin/audit-logs') }}">Audit Logs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection