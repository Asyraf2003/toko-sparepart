@extends('cashier.layouts.app')

@section('title', 'Kasir - Dashboard')

@section('page_heading')
    <div class="page-heading">
        <h3>Dashboard Kasir</h3>
    </div>
@endsection

@section('content')
    <section class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4>Aksi Cepat</h4>
                </div>
                <div class="card-body d-flex gap-2 flex-wrap">
                    <a class="btn btn-primary" href="{{ url('/cashier/transactions/today') }}">
                        Transaksi Hari Ini
                    </a>

                    <form method="post" action="{{ url('/cashier/transactions') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            + Buat Nota Baru (DRAFT)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection