@extends('shared.layouts.app')

@section('title', 'Edit Gaji')

@section('page_heading')
    @php $locked = $period->loan_deductions_applied_at !== null; @endphp

    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Edit Periode Gaji</h3>
            <p class="text-muted mb-0">{{ $period->week_start }} → {{ $period->week_end }}</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll/'.$period->id) }}">← Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $locked = $period->loan_deductions_applied_at !== null;
        $fmt = fn(int $v) => number_format((float) $v, 0, ',', '.');
    @endphp

    @if ($locked)
        <div class="alert alert-warning">
            Periode sudah <b>Diterapkan</b> ({{ $period->loan_deductions_applied_at }}). Edit baris tidak diperbolehkan. Hanya catatan header yang bisa diubah.
        </div>
    @endif

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

    <form method="post" action="{{ url('/admin/payroll/'.$period->id) }}">
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Header</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Mulai Minggu (Senin)</label>
                                <input class="form-control" type="date" name="week_start"
                                       value="{{ old('week_start', $period->week_start) }}"
                                       {{ $locked ? 'disabled' : '' }}>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Akhir Minggu (Sabtu)</label>
                                <input class="form-control" type="date" name="week_end"
                                       value="{{ old('week_end', $period->week_end) }}"
                                       {{ $locked ? 'disabled' : '' }}>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Catatan (opsional)</label>
                                <input class="form-control" type="text" name="note"
                                       value="{{ old('note', $period->note) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alasan Perubahan (wajib)</label>
                                <input class="form-control" type="text" name="reason" value="{{ old('reason') }}" required>
                            </div>

                            @if ($locked)
                                {{-- tetap kirim value agar validator date tidak kosong --}}
                                <input type="hidden" name="week_start" value="{{ old('week_start', $period->week_start) }}">
                                <input type="hidden" name="week_end" value="{{ old('week_end', $period->week_end) }}">
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lines --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Baris</div>

                        @if ($locked)
                            <div class="text-muted">Baris terkunci karena sudah diterapkan.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th style="min-width: 220px;">Karyawan</th>
                                        <th class="text-end" style="width: 180px;">Pinjaman Berjalan</th>
                                        <th class="text-end" style="width: 160px;">Gaji Kotor</th>
                                        <th class="text-end" style="width: 160px;">Potongan Pinjaman</th>
                                        <th style="min-width: 220px;">Catatan</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($employees as $idx => $e)
                                        @php
                                            $out = (int) ($outstandingByEmployeeId[$e->id] ?? 0);
                                            $existing = $lineByEmployeeId[$e->id] ?? null;

                                            $grossOld = $existing ? (string) $existing['gross_pay'] : '';
                                            $dedOld = $existing ? (string) $existing['loan_deduction'] : '';
                                            $noteOld = $existing ? (string) ($existing['note'] ?? '') : '';
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $e->name }}
                                                <input type="hidden" name="lines[{{ $idx }}][employee_id]" value="{{ $e->id }}">
                                            </td>
                                            <td class="text-end">{{ $fmt($out) }}</td>
                                            <td>
                                                <input class="form-control text-end" type="number" name="lines[{{ $idx }}][gross_pay]" min="0" step="1"
                                                       value="{{ old("lines.$idx.gross_pay", $grossOld) }}">
                                            </td>
                                            <td>
                                                <input class="form-control text-end" type="number" name="lines[{{ $idx }}][loan_deduction]" min="0" step="1"
                                                       value="{{ old("lines.$idx.loan_deduction", $dedOld) }}">
                                            </td>
                                            <td>
                                                <input class="form-control" type="text" name="lines[{{ $idx }}][note]" value="{{ old("lines.$idx.note", $noteOld) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll/'.$period->id) }}">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection