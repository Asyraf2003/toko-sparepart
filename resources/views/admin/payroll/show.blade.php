@extends('shared.layouts.app')

@section('title', 'Detail Periode Gaji')

@section('page_heading')
    @php
        $locked = $period->loan_deductions_applied_at !== null;
        $sumGross = $sum? (int) ($sum->sum_gross ?? 0) : 0;
        $sumDed = $sum? (int) ($sum->sum_deduction ?? 0) : 0;
        $sumNet = $sum? (int) ($sum->sum_net ?? 0) : 0;
        $fmt = fn(int $v) => number_format((float) $v, 0, ',', '.');
    @endphp

    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Periode Gaji</h3>
            <p class="text-muted mb-0">{{ $period->week_start }} → {{ $period->week_end }}</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll') }}">← Kembali</a>
            @if (! $locked)
                <a class="btn btn-outline-primary" href="{{ url('/admin/payroll/'.$period->id.'/edit') }}">Edit</a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @php
        $locked = $period->loan_deductions_applied_at !== null;
        $sumGross = $sum? (int) ($sum->sum_gross ?? 0) : 0;
        $sumDed = $sum? (int) ($sum->sum_deduction ?? 0) : 0;
        $sumNet = $sum? (int) ($sum->sum_net ?? 0) : 0;
        $fmt = fn(int $v) => number_format((float) $v, 0, ',', '.');
    @endphp

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Header</div>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width: 180px;">Mulai Minggu</th><td>{{ $period->week_start }}</td></tr>
                            <tr><th>Akhir Minggu</th><td>{{ $period->week_end }}</td></tr>
                            <tr><th>Catatan</th><td>{{ $period->note ?? '-' }}</td></tr>
                            <tr>
                                <th>Potongan Sudah Diterapkan?</th>
                                <td>
                                    @if ($locked)
                                        <span class="badge bg-success">YA</span>
                                        <div class="text-muted small">{{ $period->loan_deductions_applied_at }}</div>
                                    @else
                                        <span class="badge bg-secondary">TIDAK</span>
                                    @endif
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">Total</div>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width: 180px;">Gaji Kotor</th><td class="text-end">{{ $fmt($sumGross) }}</td></tr>
                            <tr><th>Potongan</th><td class="text-end">{{ $fmt($sumDed) }}</td></tr>
                            <tr><th>Gaji Bersih</th><td class="text-end fw-semibold">{{ $fmt($sumNet) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Baris</div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th class="text-end" style="width: 160px;">Gaji Kotor</th>
                                <th class="text-end" style="width: 160px;">Potongan Pinjaman</th>
                                <th class="text-end" style="width: 160px;">Gaji Bersih</th>
                                <th>Catatan</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($lines as $l)
                                <tr>
                                    <td class="fw-semibold">{{ $l->employee_name }}</td>
                                    <td class="text-end">{{ $fmt((int) $l->gross_pay) }}</td>
                                    <td class="text-end">{{ $fmt((int) $l->loan_deduction) }}</td>
                                    <td class="text-end fw-semibold">{{ $fmt((int) $l->net_paid) }}</td>
                                    <td>{{ $l->note }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">Tidak ada data</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($locked)
                        <div class="alert alert-warning mt-3 mb-0">
                            Periode sudah <b>Diterapkan</b>. Edit baris tidak diperbolehkan agar laporan & potongan hutang konsisten.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection