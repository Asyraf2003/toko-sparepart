@extends('shared.layouts.app')

@section('title', 'Gaji')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Gaji</h3>
            <p class="text-muted mb-0">Mingguan (Senin–Sabtu).</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ url('/admin/payroll/create') }}">Buat Periode Gaji</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = fn(int $v) => number_format((float) $v, 0, ',', '.');
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width: 130px;">Mulai Minggu</th>
                        <th style="width: 130px;">Akhir Minggu</th>
                        <th class="text-end" style="width: 140px;">Gaji Kotor</th>
                        <th class="text-end" style="width: 140px;">Potongan</th>
                        <th class="text-end" style="width: 140px;">Gaji Bersih</th>
                        <th style="width: 170px;">Sudah Diterapkan?</th>
                        <th>Catatan</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($periods as $p)
                        @php
                            $s = $sumByPeriodId[$p->id] ?? null;
                            $gross = $s ? (int) $s->sum_gross : 0;
                            $ded = $s ? (int) $s->sum_deduction : 0;
                            $net = $s ? (int) $s->sum_net : 0;
                            $locked = (bool) $p->loan_deductions_applied_at;
                        @endphp
                        <tr>
                            <td>{{ $p->week_start }}</td>
                            <td>{{ $p->week_end }}</td>
                            <td class="text-end">{{ $fmt($gross) }}</td>
                            <td class="text-end">{{ $fmt($ded) }}</td>
                            <td class="text-end fw-semibold">{{ $fmt($net) }}</td>
                            <td>
                                @if ($locked)
                                    <span class="badge bg-success">YA</span>
                                @else
                                    <span class="badge bg-secondary">TIDAK</span>
                                @endif
                            </td>
                            <td>{{ $p->note }}</td>
                            <td class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ url('/admin/payroll/'.$p->id) }}">Detail</a>
                                @if (! $locked)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ url('/admin/payroll/'.$p->id.'/edit') }}">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">Tidak ada data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if (is_object($periods) && method_exists($periods, 'links'))
                <div class="mt-3">
                    {{ $periods->links('vendor.pagination.mazer') }}
                </div>
            @endif
        </div>
    </div>
@endsection