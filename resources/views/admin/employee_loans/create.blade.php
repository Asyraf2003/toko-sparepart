@extends('shared.layouts.app')

@section('title', 'Tambah Pinjaman')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Tambah Pinjaman untuk {{ $employee->name }}</h3>
            <p class="text-muted mb-0">Catat pinjaman karyawan.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/employees') }}">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Terjadi kesalahan validasi</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ url('/admin/employees/'.$employee->id.'/loans') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tanggal Pinjaman</label>
                        <input class="form-control" type="date" name="loan_date" value="{{ old('loan_date') }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Jumlah Pinjaman</label>
                        <input class="form-control" type="number" name="amount" min="1" step="1" value="{{ old('amount', '0') }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Catatan (opsional)</label>
                        <input class="form-control" type="text" name="note" value="{{ old('note') }}">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Simpan Pinjaman</button>
                        <a class="btn btn-outline-secondary" href="{{ url('/admin/employees') }}">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection