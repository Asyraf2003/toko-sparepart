@extends('shared.layouts.app')

@section('title', 'Buat Payroll')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Buat Payroll Period</h3>
            <p class="text-muted mb-0">Periode mingguan (Senin–Sabtu).</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll') }}">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Validasi error</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ url('/admin/payroll') }}">
        @csrf

        <div class="row g-3">
            {{-- Periode --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Periode</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Week Start (harus Senin)</label>
                                <input class="form-control" type="date" name="week_start" value="{{ old('week_start') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Week End (harus Sabtu)</label>
                                <input class="form-control" type="date" name="week_end" value="{{ old('week_end') }}">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Note (opsional)</label>
                                <input class="form-control" type="text" name="note" value="{{ old('note') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lines --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Lines (isi gross dan/atau potongan)</div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="min-width: 220px;">Employee</th>
                                    <th class="text-end" style="width: 180px;">Outstanding Loan</th>
                                    <th class="text-end" style="width: 160px;">Gross Pay</th>
                                    <th class="text-end" style="width: 160px;">Loan Deduction</th>
                                    <th style="min-width: 220px;">Note</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($employees as $idx => $e)
                                    @php
                                        $out = (int) ($outstandingByEmployeeId[$e->id] ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $e->name }}
                                            <input type="hidden" name="lines[{{ $idx }}][employee_id]" value="{{ $e->id }}">
                                        </td>
                                        <td class="text-end">{{ number_format((float) $out, 0, ',', '.') }}</td>
                                        <td>
                                            <input class="form-control text-end" type="number" name="lines[{{ $idx }}][gross_pay]" min="0" step="1"
                                                   value="{{ old("lines.$idx.gross_pay") }}">
                                        </td>
                                        <td>
                                            <input class="form-control text-end" type="number" name="lines[{{ $idx }}][loan_deduction]" min="0" step="1"
                                                   value="{{ old("lines.$idx.loan_deduction") }}">
                                        </td>
                                        <td>
                                            <input class="form-control" type="text" name="lines[{{ $idx }}][note]" value="{{ old("lines.$idx.note") }}">
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit">Simpan Payroll</button>
                            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll') }}">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection